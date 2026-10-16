<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentReviewController extends Controller
{
    public function index()
    {
        $enrollments = StudentEnrollment::with(['approvedBy', 'accounts'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $pendingEnrollments = $enrollments->where('status', 'pending')
            ->sortByDesc('created_at')
            ->values();
        $approvedEnrollments = $enrollments->where('status', 'approved')
            ->sortByDesc(function ($enrollment) {
                return $enrollment->approved_at ?: ($enrollment->updated_at ?: $enrollment->created_at);
            })
            ->values();
        $deniedEnrollments = $enrollments->where('status', 'denied')
            ->sortByDesc(function ($enrollment) {
                return $enrollment->denied_at ?: ($enrollment->updated_at ?: $enrollment->created_at);
            })
            ->values();

        return view('pages.super_admin.students.inbox', compact(
            'enrollments',
            'pendingEnrollments',
            'approvedEnrollments',
            'deniedEnrollments'
        ));
    }

    public function show($enrollment_id)
    {
        $enrollment = StudentEnrollment::findOrFail($enrollment_id);

        return view('pages.super_admin.students.view_enrollment', compact('enrollment'));
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'enrollment_ids' => 'required|array|min:1',
            'enrollment_ids.*' => 'integer|exists:student_enrollments,id',
        ]);

        $enrollments = StudentEnrollment::with('user')
            ->whereIn('id', $data['enrollment_ids'])
            ->get();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->user && $enrollment->user->user_type === 'enroll') {
                $enrollment->user()->delete();
                continue;
            }

            $enrollment->delete();
        }

        return redirect()->route('registrar.students.inbox')
            ->with('flash_success', count($data['enrollment_ids']) . ' forms deleted successfully.');
    }

    public function approve($enrollment_id)
    {
        $enrollment = StudentEnrollment::whereKey($enrollment_id)
            ->where('status', 'pending')
            ->firstOrFail();
        $enrollment->status = 'approved';
        $enrollment->approved_by = Auth::id();
        $enrollment->approved_at = now();
        $enrollment->save();

        return redirect()->route('registrar.students.inbox')->with('flash_success', 'Enrollment application approved.');
    }

    public function deny(Request $request, $enrollment_id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $enrollment = StudentEnrollment::whereKey($enrollment_id)
            ->where('status', 'pending')
            ->firstOrFail();
        $enrollment->status = 'denied';
        $enrollment->denied_by = Auth::id();
        $enrollment->denied_reason = $request->reason;
        $enrollment->denied_at = now();
        $enrollment->save();

        return redirect()->route('registrar.students.inbox')->with('flash_success', 'Enrollment application denied.');
    }

    public function markEnrolled($enrollment_id)
    {
        $enrollment = StudentEnrollment::whereKey($enrollment_id)
            ->where('status', 'approved')
            ->whereNull('enrolled_at')
            ->firstOrFail();

        $accountTypes = $enrollment->accounts()->pluck('student_enrollment_accounts.account_type')->unique();
        if(!$accountTypes->contains('Student') || !$accountTypes->contains('Parent')) {
            return redirect()->route('registrar.students.inbox')
                ->withErrors(['enrollment' => 'Create and link at least one Student and one Parent account before marking this enrollment complete.']);
        }

        $enrollment->enrolled_at = now();
        $enrollment->save();

        return redirect()->route('registrar.students.inbox')
            ->with('flash_success', 'Enrollment marked as enrolled. This action cannot be reverted.');
    }
}
