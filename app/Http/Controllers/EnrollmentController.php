<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Helpers\AccountCredentials;
use App\Models\AccountCreationNotice;
use App\Models\AccountCreationNoticeRecipient;
use App\Models\BillingNoticeRecipient;
use App\Models\StudentEnrollment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'surname' => 'required|string',
            'first_name' => 'required|string',
            'middle_name' => 'sometimes|nullable|string',
            'dob' => 'required|date',
            'place_of_birth_town' => 'required|string',
            'gender' => 'required|string|in:Male,Female',
            'religion' => 'required|string',
            'home_town' => 'required|string',
            'father_name' => 'sometimes|nullable|string',
            'father_occupation' => 'sometimes|nullable|string',
            'mother_name' => 'sometimes|nullable|string',
            'mother_occupation' => 'sometimes|nullable|string',
            'guardian_name' => 'sometimes|nullable|string',
            'guardian_occupation' => 'sometimes|nullable|string',
            'guardian_relation' => 'sometimes|nullable|string',
            'contact_number' => 'required|string',
            'last_school_name' => 'sometimes|nullable|string',
            'last_school_type' => 'sometimes|nullable|string|in:PRIVATE,PUBLIC',
            'last_school_address' => 'sometimes|nullable|string',
            'grade_enrolling' => 'required|string',
        ]);

        $data['place_of_birth_province'] = $data['place_of_birth_town'];
        $data['home_province'] = $data['home_town'];

        $existingUser = Auth::user();
        $isExistingAccount = $existingUser && $existingUser->user_type !== 'enroll';

        if($isExistingAccount) {
            $user = $existingUser;
            $username = $user->username;
            $password = null;
        } else {
            $today = now()->format('Ymd');
            $count = User::where('user_type', 'enroll')
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $username = 'ENROLL' . $today . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
            $password = AccountCredentials::randomPassword(8);

            $user = User::create([
                'name' => Qs::formatNameFromParts($data['surname'], $data['first_name'], $data['middle_name'] ?? null),
                'username' => $username,
                'email' => null,
                'password' => Hash::make($password),
                'user_type' => 'enroll',
                'photo' => Qs::getDefaultUserImage(),
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'code' => strtoupper(Str::random(10)),
            ]);
        }

        $enrollment = StudentEnrollment::create([
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'dob' => $data['dob'],
            'place_of_birth_town' => $data['place_of_birth_town'],
            'place_of_birth_province' => $data['place_of_birth_province'],
            'gender' => $data['gender'],
            'religion' => $data['religion'],
            'home_town' => $data['home_town'],
            'home_province' => $data['home_province'],
            'father_name' => $data['father_name'] ?? null,
            'father_occupation' => $data['father_occupation'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
            'guardian_occupation' => $data['guardian_occupation'] ?? null,
            'guardian_relation' => $data['guardian_relation'] ?? null,
            'contact_number' => $data['contact_number'],
            'last_school_name' => $data['last_school_name'] ?? null,
            'last_school_type' => $data['last_school_type'] ?? null,
            'last_school_address' => $data['last_school_address'] ?? null,
            'grade_enrolling' => $data['grade_enrolling'],
            'account_username' => $username,
            'password' => $password,
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        if($isExistingAccount && in_array($user->user_type, ['student', 'parent'])) {
            $enrollment->accounts()->syncWithoutDetaching([
                $user->id => ['account_type' => ucfirst($user->user_type)],
            ]);
        }

        if($isExistingAccount && $user->user_type === 'parent') {
            return redirect()->route('parent_enrollment', ['tab' => 'pending'])
                ->with('flash_success', 'Your enrollment form has been submitted successfully.');
        }

        if($isExistingAccount) {
            return redirect()->route('pending_submissions')
                ->with('flash_success', 'Your enrollment form has been submitted successfully.');
        }

        Auth::login($user);

        return redirect()->route('pending_submissions')->with('pop_success', 'Your enrollment form has been submitted successfully! Your temporary account details are as follows. Username: ' . $username . '. Password: ' . $password . '. Please log in with the given details through the Login Page to access your Pending Submissions and receive the results of your submission.');
    }

    public function accountInbox()
    {
        $notices = AccountCreationNoticeRecipient::with('notice.studentEnrollment')
            ->where('user_id', Auth::id())
            ->whereHas('notice', function ($query) {
                $query->whereNull('temporary_account_delete_at')
                    ->orWhere('temporary_account_delete_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->when(Auth::user()->user_type === 'parent', function ($query) {
                $query->whereHas('notice.studentEnrollment', function ($enrollmentQuery) {
                    $enrollmentQuery->whereHas('enrolledStudent.parents', function ($parentQuery) {
                        $parentQuery->where('users.id', Auth::id());
                    })->orWhereHas('accounts', function ($accountQuery) {
                        $accountQuery->where('users.id', Auth::id())
                            ->where('student_enrollment_accounts.account_type', 'Parent');
                    })->orWhereHas('enrolledStudent.student_record', function ($recordQuery) {
                        $recordQuery->where('my_parent_id', Auth::id());
                    });
                });
            })
            ->get();
        $billingNotices = BillingNoticeRecipient::with('notice')
            ->where('user_id', Auth::id())
            ->whereHas('notice', function ($noticeQuery) {
                $noticeQuery->whereHas('student.parents', function ($parentQuery) {
                    $parentQuery->where('users.id', Auth::id());
                })->orWhereHas('student.student_record', function ($recordQuery) {
                    $recordQuery->where('my_parent_id', Auth::id());
                })->orWhereHas('student.student_enrollment.accounts', function ($accountQuery) {
                    $accountQuery->where('users.id', Auth::id())
                        ->where('student_enrollment_accounts.account_type', 'Parent');
                });
            })
            ->latest()
            ->get();

        return view('pages.other.account_inbox', compact('notices', 'billingNotices'));
    }

    public function pendingSubmissions()
    {
        $user = Auth::user();

        if ($user->user_type === 'enroll') {
            $expiredNotice = StudentEnrollment::where('user_id', $user->id)
                ->whereNotNull('temporary_account_delete_at')
                ->where('temporary_account_delete_at', '<=', now())
                ->exists();

            if ($expiredNotice) {
                $user->delete();
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect()->route('public_home')->with('flash_success', 'Your temporary account has been deleted after the 3-day notice period.');
            }
        } elseif ($user->user_type === 'parent') {
            $expiredEnrollments = StudentEnrollment::where('user_id', $user->id)
                ->whereNotNull('account_created_acknowledged_at')
                ->whereNotNull('temporary_account_delete_at')
                ->where('temporary_account_delete_at', '<=', now())
                ->get();

            foreach ($expiredEnrollments as $enrollment) {
                $enrollment->account_created_notice_at = null;
                $enrollment->account_created_acknowledged_at = null;
                $enrollment->temporary_account_delete_at = null;
                $enrollment->save();
            }

            AccountCreationNotice::whereNotNull('temporary_account_delete_at')
                ->where('temporary_account_delete_at', '<=', now())
                ->whereHas('studentEnrollment', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->delete();
        }

        $enrollments = StudentEnrollment::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        if ($user->user_type === 'parent') {
            $enrollments = $enrollments->whereNull('account_created_acknowledged_at')->values();
        }
        $accountCreatedEnrollments = $enrollments->whereNotNull('account_created_notice_at')
            ->filter(function ($enrollment) use ($user) {
                return $user->user_type !== 'parent'
                    || !$enrollment->temporary_account_delete_at
                    || $enrollment->temporary_account_delete_at->isFuture();
            });
        $accountCreationNotices = AccountCreationNotice::with('studentEnrollment')
            ->whereHas('studentEnrollment', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->when($user->user_type === 'parent', function ($enrollmentQuery) {
                        $enrollmentQuery->whereNull('account_created_acknowledged_at');
                    });
            })
            ->where(function ($query) {
                $query->whereNull('temporary_account_delete_at')
                    ->orWhere('temporary_account_delete_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.other.pending_submissions', compact('enrollments', 'accountCreatedEnrollments', 'accountCreationNotices'));
    }

    public function acknowledgeDenial(Request $request, $enrollment_id)
    {
        $request->validate([
            'acknowledged' => 'accepted',
        ]);

        $user = Auth::user();
        $enrollment = StudentEnrollment::where('id', $enrollment_id)
            ->where('user_id', $user->id)
            ->where('status', 'denied')
            ->firstOrFail();

        $enrollment->user()->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public_home')->with('flash_success', 'Your temporary account has been deleted.');
    }

    public function acknowledgeAccountCreatedNotice(Request $request, $enrollment_id)
    {
        $request->validate([
            'acknowledged' => 'sometimes|accepted',
        ]);

        $user = Auth::user();
        $enrollment = StudentEnrollment::where('id', $enrollment_id)
            ->where('user_id', $user->id)
            ->whereNotNull('enrolled_at')
            ->whereNotNull('account_created_notice_at')
            ->firstOrFail();

        $enrollment->account_created_acknowledged_at = now();
        $enrollment->temporary_account_delete_at = now()->addDays(3);
        $enrollment->save();

        return redirect()->route('pending_submissions')
            ->with('flash_success', $user->user_type === 'parent'
                ? 'Acknowledgment recorded. This notice will be deleted after 3 days.'
                : 'Acknowledgment recorded. Your temporary account will be deleted after 3 days.');
    }

    public function acknowledgeAccountCreationNotice(Request $request, $notice_id)
    {
        $request->validate([
            'acknowledged' => 'accepted',
        ]);

        $user = Auth::user();
        $notice = AccountCreationNotice::with('studentEnrollment')
            ->whereKey($notice_id)
            ->whereHas('studentEnrollment', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->firstOrFail();

        if (!$notice->acknowledged_at) {
            $notice->acknowledged_at = now();
            $notice->temporary_account_delete_at = now()->addDays(3);
            $notice->save();

            if ($user->user_type !== 'parent') {
                $enrollment = $notice->studentEnrollment;
                $enrollment->account_created_acknowledged_at = $notice->acknowledged_at;
                $enrollment->temporary_account_delete_at = $notice->temporary_account_delete_at;
                $enrollment->save();
            }
        }

        return redirect()->route('pending_submissions')
            ->with('flash_success', $user->user_type === 'parent'
                ? 'Acknowledgment recorded. This notice will be deleted after 3 days.'
                : 'Acknowledgment recorded. Your temporary account will be deleted after 3 days.');
    }

    public function acknowledgeParentAccountCreationNotice(Request $request, $recipient_id)
    {
        $request->validate([
            'acknowledged' => 'sometimes|accepted',
        ]);

        $parent = Auth::user();
        abort_unless($parent->user_type === 'parent', 403);

        $recipient = AccountCreationNoticeRecipient::with('notice.studentEnrollment')
            ->whereKey($recipient_id)
            ->where('user_id', $parent->id)
            ->whereHas('notice.studentEnrollment', function ($query) use ($parent) {
                $query->where('user_id', $parent->id);
            })
            ->first();

        if (!$recipient) {
            return redirect()->back()
                ->with('flash_success', 'This notice has already been acknowledged or removed.');
        }

        DB::transaction(function () use ($recipient) {
            $enrollment = $recipient->notice->studentEnrollment;
            $enrollment->account_created_acknowledged_at = now();
            $enrollment->save();
            $recipient->delete();
        });

        return redirect()->back()
            ->with('flash_success', 'Acknowledgment recorded. The pending submission and notice were removed.');
    }
}
