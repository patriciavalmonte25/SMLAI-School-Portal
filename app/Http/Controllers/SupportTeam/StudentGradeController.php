<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Models\StudentGradeSheet;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentGradeController extends Controller
{
    public function show($student_id)
    {
        list($student, $gradeSheet, $isLinkedParent) = $this->getGradeContext($student_id);

        $isAdministrator = in_array(Auth::user()->user_type, ['administrator', 'admin']);
        $isTeacher = Auth::user()->user_type === 'teacher';
        $isStudent = Auth::id() === (int) $student->id;
        $isAllUsers = $gradeSheet->visibility === 'All Users';
        $canView = $isAdministrator || $isTeacher || ($isAllUsers && $isStudent)
            || (Auth::user()->user_type === 'parent' && $isLinkedParent);

        abort_unless($canView, 403);

        return $this->renderGrades($student, $gradeSheet, false);
    }

    public function edit($student_id)
    {
        list($student, $gradeSheet) = $this->getGradeContext($student_id);
        $actor = Auth::user();
        abort_unless(in_array($actor->user_type, ['administrator', 'admin', 'teacher']), 403);

        return $this->renderGrades($student, $gradeSheet, true);
    }

    private function renderGrades($student, $gradeSheet, $canEditGrades)
    {
        $layout = $gradeSheet->layout;
        if (!is_array($layout) || !isset($layout['columns'], $layout['rows'])) {
            $layout = $this->blankLayout();
        }

        return view('pages.support_team.students.grades', [
            'student' => $student,
            'grade_sheet' => $gradeSheet,
            'layout' => $layout,
            'can_edit_grades' => $canEditGrades,
            'can_manage_visibility' => in_array(Auth::user()->user_type, ['administrator', 'admin', 'teacher']),
            'is_edit_page' => $canEditGrades,
        ]);
    }

    public function saveLayout(Request $request, $student_id)
    {
        list($student, $gradeSheet, $isLinkedParent) = $this->getGradeContext($student_id);
        $actor = Auth::user();
        $canEditGrades = in_array($actor->user_type, ['administrator', 'admin', 'teacher']);

        abort_unless($canEditGrades, 403);

        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.columns' => 'required|array|min:2|max:24',
            'layout.columns.*.key' => 'required|string|max:80|distinct',
            'layout.columns.*.title' => 'nullable|string|max:100',
            'layout.rows' => 'present|array|max:200',
            'layout.rows.*.id' => 'required|string|max:100',
            'layout.rows.*.cells' => 'sometimes|array',
            'layout.rows.*.cells.*' => 'nullable|string|max:250',
        ]);

        $gradeSheet->layout = [
            'columns' => array_values($validated['layout']['columns']),
            'rows' => array_values($validated['layout']['rows']),
        ];
        $gradeSheet->save();

        return response()->json([
            'ok' => true,
            'msg' => 'Student grades saved successfully.',
            'updated_at' => optional($gradeSheet->updated_at)->toDateTimeString(),
        ]);
    }

    public function saveVisibility(Request $request, $student_id)
    {
        $actor = Auth::user();
        abort_unless(in_array($actor->user_type, ['administrator', 'admin', 'teacher']), 403);

        list($student, $gradeSheet) = $this->getGradeContext($student_id);
        $data = $request->validate([
            'visibility' => 'required|string|in:Teaching Staff,All Users',
        ]);

        $gradeSheet->visibility = $data['visibility'];
        $gradeSheet->save();

        return response()->json([
            'ok' => true,
            'msg' => 'Grade visibility updated successfully.',
            'visibility' => $gradeSheet->visibility,
        ]);
    }

    private function getGradeContext($student_id)
    {
        $decodedStudentId = is_numeric($student_id) ? (int) $student_id : (int) Qs::decodeHash($student_id);
        abort_unless($decodedStudentId > 0, 404);

        $student = User::where('user_type', 'student')->findOrFail($decodedStudentId);
        $gradeSheet = StudentGradeSheet::firstOrCreate(
            ['student_user_id' => $student->id],
            [
                'layout' => $this->blankLayout(),
                'visibility' => 'Teaching Staff',
            ]
        );

        $isLinkedParent = $student->parents()->where('users.id', Auth::id())->exists()
            || StudentRecord::where('user_id', $student->id)
                ->where('my_parent_id', Auth::id())
                ->exists();

        if (!$isLinkedParent && Auth::user()->user_type === 'parent') {
            $isLinkedParent = StudentEnrollment::where(function ($query) use ($student) {
                    $query->where('enrolled_student_user_id', $student->id)
                        ->orWhere('user_id', $student->id);
                })
                ->whereHas('accounts', function ($query) {
                    $query->where('users.id', Auth::id())
                        ->where('student_enrollment_accounts.account_type', 'Parent');
                })
                ->exists();
        }

        return [$student, $gradeSheet, $isLinkedParent];
    }

    private function blankLayout()
    {
        return [
            'columns' => [
                ['key' => 'subject', 'title' => 'Subject'],
                ['key' => 'grade', 'title' => 'Grade'],
            ],
            'rows' => [],
        ];
    }
}