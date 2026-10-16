<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AccountCreationNotice;
use App\Models\AccountCreationNoticeRecipient;
use App\Models\StudentEnrollment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutboxController extends Controller
{
    public function index()
    {
        $enrollments = StudentEnrollment::with(['user', 'accounts'])
            ->whereHas('user', function ($query) {
                $query->whereIn('user_type', ['enroll', 'parent']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $notices = AccountCreationNotice::with(['studentEnrollment', 'sentBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $linkedAccounts = $enrollments->mapWithKeys(function ($enrollment) {
            $accounts = $enrollment->accounts
                ->reject(function ($user) use ($enrollment) {
                    return optional($enrollment->user)->user_type === 'parent'
                        && $user->pivot->account_type === 'Parent';
                })
                ->map(function ($user) use ($enrollment) {
                $password = null;
                if($user->password_encrypted) {
                    try {
                        $password = decrypt($user->password_encrypted);
                    } catch (\Throwable $exception) {
                        $password = null;
                    }
                } elseif($user->user_type === 'student' && (int) $enrollment->enrolled_student_user_id === (int) $user->id) {
                    $password = 'password';
                }

                return [
                    'account_type' => $user->pivot->account_type,
                    'username' => $user->username,
                    'password' => $password,
                ];
                })
                ->values();

            return [$enrollment->id => $accounts];
        });

        return view('pages.super_admin.students.outbox', compact('enrollments', 'notices', 'linkedAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_enrollment_id' => 'required|integer|exists:student_enrollments,id',
            'accounts' => 'required|array|min:1',
            'accounts.*.account_type' => 'required|string|in:Student,Parent',
            'accounts.*.username' => 'required|string|max:100|distinct',
            'accounts.*.password' => 'required|string|max:100',
        ]);

        $enrollment = StudentEnrollment::findOrFail($data['student_enrollment_id']);
        $recipients = [];
        foreach ($data['accounts'] as $index => $account) {
            $userType = $account['account_type'] === 'Student' ? 'student' : 'parent';
            $user = $enrollment->accounts()
                ->where('users.username', $account['username'])
                ->where('user_type', $userType)
                ->wherePivot('account_type', $account['account_type'])
                ->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    "accounts.{$index}.username" => 'The username must belong to an account of the selected type.',
                ]);
            }

            try {
                $password = $user->password_encrypted ? decrypt($user->password_encrypted) : null;
            } catch (\Throwable $exception) {
                $password = null;
            }
            if(!$password && $userType === 'student' && (int) $enrollment->enrolled_student_user_id === (int) $user->id) {
                $password = 'password';
            }
            if(!$password || $password !== $account['password']) {
                throw ValidationException::withMessages([
                    "accounts.{$index}.password" => 'The password does not match the selected linked account.',
                ]);
            }

            $recipients[] = [
                'user' => $user,
                'account_type' => $account['account_type'],
                'username' => $account['username'],
                'password' => $password,
            ];
        }

        DB::transaction(function () use ($data, $recipients, $enrollment) {
            $notice = AccountCreationNotice::create([
                'student_enrollment_id' => $data['student_enrollment_id'],
                'accounts' => array_map(function ($recipient) {
                    return [
                        'account_type' => $recipient['account_type'],
                        'username' => $recipient['username'],
                        'password' => $recipient['password'],
                    ];
                }, $recipients),
                'sent_by' => Auth::id(),
            ]);

            if ($enrollment->user && $enrollment->user->user_type === 'parent') {
                $studentAccount = collect($recipients)->firstWhere('account_type', 'Student');
                if ($studentAccount) {
                    AccountCreationNoticeRecipient::create([
                        'account_creation_notice_id' => $notice->id,
                        'user_id' => $enrollment->user_id,
                        'account_type' => $studentAccount['account_type'],
                        'username' => $studentAccount['username'],
                    ]);
                }
            }
        });

        return redirect()->route('registrar.students.outbox')
            ->with('flash_success', 'Account creation notice sent to the enrollee.');
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'notice_ids' => 'required|array|min:1',
            'notice_ids.*' => 'required|integer|distinct|exists:account_creation_notices,id',
        ]);

        AccountCreationNotice::whereIn('id', $data['notice_ids'])->delete();

        return redirect()->route('registrar.students.outbox')
            ->with('flash_success', 'Selected notices deleted successfully.');
    }
}
