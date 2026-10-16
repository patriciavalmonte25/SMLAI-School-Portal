<?php

namespace App\Http\Controllers\MyParent;
use App\Http\Controllers\Controller;
use App\Models\AccountCreationNotice;
use App\Models\AccountCreationNoticeRecipient;
use App\Models\BillingNoticeRecipient;
use App\Models\StudentEnrollment;
use App\Repositories\StudentRepo;
use Illuminate\Support\Facades\Auth;

class MyController extends Controller
{
    protected $student;
    public function __construct(StudentRepo $student)
    {
        $this->student = $student;
    }

    public function children()
    {
        $parent = Auth::user();
        $enrollmentStudentIds = StudentEnrollment::whereNotNull('enrolled_student_user_id')
            ->where(function ($query) use ($parent) {
                $query->where('user_id', $parent->id)
                    ->orWhereHas('accounts', function ($accountQuery) use ($parent) {
                        $accountQuery->where('users.id', $parent->id)
                            ->where('student_enrollment_accounts.account_type', 'Parent');
                    });
            })
            ->pluck('enrolled_student_user_id');
        $studentIds = $parent->children()->pluck('users.id')
            ->merge(\App\Models\StudentRecord::where('my_parent_id', $parent->id)->pluck('user_id'))
            ->merge($enrollmentStudentIds)
            ->unique()
            ->values();

        $data['students'] = \App\User::whereIn('id', $studentIds)
            ->where('user_type', 'student')
            ->with(['student_record.my_class', 'student_record.section', 'student_enrollment.my_class', 'student_enrollment.section', 'gradeSheet'])
            ->get()
            ->sortBy(function ($student) {
                $sectionId = optional($student->student_record)->section_id
                    ?: optional($student->student_enrollment)->section_id;

                return sprintf('%010d-%s', $sectionId ?: 2147483647, strtolower($student->name));
            })
            ->values();

        return view('pages.parent.children', $data);
    }

    public function enrollment()
    {
        $parentId = Auth::id();
        $enrollments = StudentEnrollment::where('user_id', $parentId)
            ->whereNull('account_created_acknowledged_at')
            ->orderBy('created_at', 'desc')
            ->get();
        $accountCreationNotices = AccountCreationNotice::with('studentEnrollment')
            ->whereHas('studentEnrollment', function ($query) use ($parentId) {
                $query->where('user_id', $parentId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $notices = AccountCreationNoticeRecipient::with('notice.studentEnrollment')
            ->where('user_id', $parentId)
            ->whereHas('notice', function ($query) {
                $query->whereNull('temporary_account_delete_at')
                    ->orWhere('temporary_account_delete_at', '>', now());
            })
            ->whereHas('notice.studentEnrollment', function ($query) use ($parentId) {
                $query->whereHas('enrolledStudent.parents', function ($parentQuery) use ($parentId) {
                    $parentQuery->where('users.id', $parentId);
                })->orWhereHas('accounts', function ($accountQuery) use ($parentId) {
                    $accountQuery->where('users.id', $parentId)
                        ->where('student_enrollment_accounts.account_type', 'Parent');
                })->orWhereHas('enrolledStudent.student_record', function ($recordQuery) use ($parentId) {
                    $recordQuery->where('my_parent_id', $parentId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $billingNotices = BillingNoticeRecipient::with('notice')
            ->where('user_id', $parentId)
            ->whereHas('notice', function ($noticeQuery) use ($parentId) {
                $noticeQuery->whereHas('student.parents', function ($parentQuery) use ($parentId) {
                    $parentQuery->where('users.id', $parentId);
                })->orWhereHas('student.student_record', function ($recordQuery) use ($parentId) {
                    $recordQuery->where('my_parent_id', $parentId);
                })->orWhereHas('student.student_enrollment.accounts', function ($accountQuery) use ($parentId) {
                    $accountQuery->where('users.id', $parentId)
                        ->where('student_enrollment_accounts.account_type', 'Parent');
                });
            })
            ->latest()
            ->get();

        return view('pages.parent.enrollment', compact(
            'enrollments',
            'accountCreationNotices',
            'notices',
            'billingNotices'
        ));
    }

}