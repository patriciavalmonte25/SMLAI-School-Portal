<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BillingList;
use App\Models\BillingNotice;
use App\Models\BillingNoticeRecipient;
use App\Models\MyClass;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingNoticeController extends Controller
{
    public function index()
    {
        $classes = MyClass::schoolOrder()->get();
        $billingLists = BillingList::all()->sortBy(function ($billingList) {
            return $billingList->grade_key === 'k' ? 0 : (int) $billingList->grade_key;
        })->values();
        $gradeLayouts = $billingLists->mapWithKeys(function ($billingList) {
            $layout = is_array($billingList->layout) ? $billingList->layout : json_decode($billingList->layout, true);
            return [$billingList->grade_key => is_array($layout) ? $layout : ['columns' => [], 'rows' => []]];
        });

        $notices = BillingNotice::with(['student', 'section', 'recipients.user', 'sentBy'])
            ->whereNull('paid_at')->latest()->get();
        $paidNotices = BillingNotice::with(['student', 'section', 'recipients.user', 'sentBy'])
            ->whereNotNull('paid_at')->latest('paid_at')->get();

        return view('pages.accountant.notices', compact('classes', 'billingLists', 'gradeLayouts', 'notices', 'paidNotices'));
    }

    public function students($class_id)
    {
        $class = MyClass::findOrFail($class_id);
        $records = StudentRecord::select('student_records.*')
            ->join('users', 'users.id', '=', 'student_records.user_id')
            ->with(['user', 'section'])
            ->where('student_records.my_class_id', $class->id)
            ->where('student_records.grad', 0)
            ->orderBy('users.name')
            ->get();
        $students = [];
        foreach ($records as $record) {
            if (!$record->user) {
                continue;
            }
            $students[$record->user_id] = $this->studentOption(
                $record->user,
                $class,
                optional($record->section)->name
            );
        }

        $enrollments = StudentEnrollment::with(['enrolledStudent', 'section'])
            ->where('my_class_id', $class->id)
            ->whereNotNull('enrolled_student_user_id')
            ->orderBy('id')
            ->get();
        $enrolledStudentIds = $enrollments->pluck('enrolled_student_user_id')->unique();
        $graduatedStudentIds = StudentRecord::whereIn('user_id', $enrolledStudentIds)
            ->where('grad', 1)
            ->pluck('user_id')
            ->all();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->enrolledStudent;
            if (!$student || $student->user_type !== 'student' || isset($students[$student->id]) || in_array($student->id, $graduatedStudentIds)) {
                continue;
            }
            $students[$student->id] = $this->studentOption(
                $student,
                $class,
                optional($enrollment->section)->name
            );
        }

        return response()->json(collect($students)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|integer|exists:my_classes,id',
            'student_user_id' => 'required|integer|exists:users,id',
            'layout' => 'required|array',
            'layout.columns' => 'required|array|min:2|max:24',
            'layout.columns.*.key' => 'required|string|max:80|distinct',
            'layout.columns.*.title' => 'nullable|string|max:100',
            'layout.rows' => 'required|array|min:1|max:200',
            'layout.rows.*.id' => 'required|string|max:100',
            'layout.rows.*.cells' => 'required|array',
            'layout.rows.*.cells.*' => 'nullable|string|max:250',
        ]);

        $class = MyClass::findOrFail($data['class_id']);
        $record = StudentRecord::with(['user', 'my_class', 'section'])
            ->where('user_id', $data['student_user_id'])
            ->where('my_class_id', $data['class_id'])
            ->where('grad', 0)
            ->first();

        $enrollment = null;
        if (!$record) {
            $enrollment = StudentEnrollment::with(['enrolledStudent', 'section'])
                ->where('my_class_id', $data['class_id'])
                ->where('enrolled_student_user_id', $data['student_user_id'])
                ->latest('id')
                ->first();
            abort_unless($enrollment && $enrollment->enrolledStudent && $enrollment->enrolledStudent->user_type === 'student', 404);
        }

        $student = $record ? $record->user : $enrollment->enrolledStudent;
        $studentRecordId = $record ? $record->id : null;
        $sectionId = $record ? $record->section_id : $enrollment->section_id;
        $sectionName = $record ? optional($record->section)->name : optional($enrollment->section)->name;
        $parents = $this->linkedParents($student->id);

        if ($parents->isEmpty()) {
            throw ValidationException::withMessages([
                'student_user_id' => 'This student has no linked parent accounts to receive the notice.',
            ]);
        }

        DB::transaction(function () use ($data, $class, $student, $studentRecordId, $sectionId, $sectionName, $parents) {
            $notice = BillingNotice::create([
            'student_user_id' => $student->id,
            'student_record_id' => $studentRecordId,
            'section_id' => $sectionId,
            'student_name' => $student->name,
                'student_class' => $class->name ?: 'Class',
            'student_section' => $sectionName,
                'layout' => [
                    'columns' => array_values($data['layout']['columns']),
                    'rows' => array_values($data['layout']['rows']),
                ],
                'sent_by' => Auth::id(),
            ]);

            foreach ($parents as $parent) {
                $notice->recipients()->create([
                    'user_id' => $parent->id,
                    'parent_name' => $parent->name,
                ]);
            }
        });

        return redirect()->to(route('accountant.notices.index') . '#manage-notices');
    }

    public function markPaid($notice_id)
    {
        $notice = BillingNotice::whereNull('paid_at')->findOrFail($notice_id);
        $notice->paid_at = now();
        $notice->save();

        return redirect()->to(route('accountant.notices.index') . '#manage-notices');
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'notice_ids' => 'required|array|min:1',
            'notice_ids.*' => 'required|integer|distinct|exists:billing_notices,id',
        ]);

        DB::transaction(function () use ($data) {
            BillingNoticeRecipient::whereIn('billing_notice_id', $data['notice_ids'])->delete();
            BillingNotice::whereIn('id', $data['notice_ids'])->delete();
        });

        $tab = $request->input('notice_tab') === 'paid' ? 'paid-notices' : 'manage-notices';
        return redirect()->to(route('accountant.notices.index') . '#' . $tab);
    }

    private function linkedParents($studentUserId)
    {
        $student = User::find($studentUserId);
        $parentIds = $student ? $student->parents()->pluck('users.id') : collect();
        $parentIds = $parentIds->merge(StudentRecord::where('user_id', $studentUserId)
            ->whereNotNull('my_parent_id')
            ->pluck('my_parent_id'));

        $enrollments = StudentEnrollment::where(function ($query) use ($studentUserId) {
            $query->where('enrolled_student_user_id', $studentUserId)
                ->orWhere('user_id', $studentUserId);
        })
            ->orWhereHas('accounts', function ($query) use ($studentUserId) {
                $query->where('users.id', $studentUserId)
                    ->where('student_enrollment_accounts.account_type', 'Student');
            })
            ->get();

        foreach ($enrollments as $enrollment) {
            $parentIds = $parentIds->merge(
                $enrollment->accounts()->wherePivot('account_type', 'Parent')->pluck('users.id')
            );
        }

        return User::whereIn('id', $parentIds->unique()->values())
            ->where('user_type', 'parent')
            ->orderBy('name')
            ->get();
    }

    private function studentOption($student, $class, $sectionName)
    {
        $parents = $this->linkedParents($student->id);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'class_name' => $class->name,
            'section_name' => $sectionName,
            'parents' => $parents->map(function ($parent) {
                return ['id' => $parent->id, 'name' => $parent->name];
            })->values(),
        ];
    }
}