<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Support\Facades\Auth;

class ClassStudentController extends Controller
{
    public function myClass()
    {
        $user = Auth::user();

        if ($user->user_type === 'parent') {
            $childIds = $this->childIdsForParent($user);
            $classIds = $this->classIdsForChildren($childIds);
            $requestedClassId = request()->query('class_id');

            if ($requestedClassId !== null) {
                abort_unless(is_numeric($requestedClassId) && $classIds->contains((int) $requestedClassId), 403);

                return $this->show((int) $requestedClassId, true);
            }

            $classes = MyClass::whereIn('id', $classIds)->orderBy('name')->get();
            if ($classes->count() === 1) {
                return $this->show($classes->first()->id, true);
            }

            return view('pages.parent.class_lists', compact('classes'));
        }

        abort_unless($user->user_type === 'student', 403);

        $studentRecord = StudentRecord::where('user_id', Auth::id())
            ->where('grad', 0)
            ->first();
        $classId = optional($studentRecord)->my_class_id
            ?: StudentEnrollment::where('enrolled_student_user_id', Auth::id())
                ->whereNotNull('my_class_id')
                ->value('my_class_id');

        abort_unless($classId, 404);

        return $this->show($classId, true);
    }

    protected function childIdsForParent($parent)
    {
        $enrollmentStudentIds = StudentEnrollment::whereNotNull('enrolled_student_user_id')
            ->where(function ($query) use ($parent) {
                $query->where('user_id', $parent->id)
                    ->orWhereHas('accounts', function ($accountQuery) use ($parent) {
                        $accountQuery->where('users.id', $parent->id)
                            ->where('student_enrollment_accounts.account_type', 'Parent');
                    });
            })
            ->pluck('enrolled_student_user_id');

        $childIds = $parent->children()->pluck('users.id')
            ->merge(StudentRecord::where('my_parent_id', $parent->id)->pluck('user_id'))
            ->merge($enrollmentStudentIds)
            ->filter()
            ->unique()
            ->values();

        return User::whereIn('id', $childIds)
            ->where('user_type', 'student')
            ->pluck('id');
    }

    protected function classIdsForChildren($childIds)
    {
        $records = StudentRecord::whereIn('user_id', $childIds)
            ->where('grad', 0)
            ->whereNotNull('my_class_id')
            ->get(['user_id', 'my_class_id']);

        $recordStudentIds = $records->pluck('user_id')->unique();
        $enrollments = StudentEnrollment::whereIn('enrolled_student_user_id', $childIds)
            ->whereNotNull('my_class_id');

        if ($recordStudentIds->isNotEmpty()) {
            $enrollments->whereNotIn('enrolled_student_user_id', $recordStudentIds);
        }

        return $records->pluck('my_class_id')
            ->merge($enrollments->pluck('my_class_id'))
            ->map(function ($classId) {
                return (int) $classId;
            })
            ->unique()
            ->values();
    }

    public function show($class_id, $isStudent = false)
    {
        $my_class = MyClass::with(['class_type', 'adviser'])->find($class_id);

        if (!$my_class) {
            return Qs::goWithDanger();
        }

        // Use the active section teacher as primary advisor, then fallback to first section teacher.
        $advisor_section = Section::where('my_class_id', $class_id)
            ->whereNotNull('teacher_id')
            ->with('teacher')
            ->orderByDesc('active')
            ->orderBy('name')
            ->first();

        $advisor = $my_class->adviser ?: ($advisor_section ? $advisor_section->teacher : null);

        $isSeniorHigh = optional($my_class->class_type)->code === 'SHS'
            || optional($my_class->class_type)->name === 'Senior Highschool'
            || preg_match('/(^|\b)(11|12)(\b|$)/', $my_class->name)
            || preg_match('/Grade\s*(11|12)/i', $my_class->name);

        // Fetch students via StudentRecord or StudentEnrollment belonging to this class
        $srUserIds = StudentRecord::where('my_class_id', $class_id)
            ->where('grad', 0)
            ->pluck('user_id');

        $enrollmentUserIds = StudentEnrollment::where('my_class_id', $class_id)
            ->whereNotNull('enrolled_student_user_id')
            ->pluck('enrolled_student_user_id');

        $studentUserIds = $srUserIds->concat($enrollmentUserIds)->unique()->filter();

        $students = User::whereIn('id', $studentUserIds)
            ->where('user_type', 'student')
            ->with(['student_record', 'student_enrollment', 'gradeSheet'])
            ->get()
            ->sort(function ($a, $b) {
                // Male first (0), then Female (1), then others (2)
                $genderRankA = strtolower($a->gender ?? '') === 'male' ? 0 : (strtolower($a->gender ?? '') === 'female' ? 1 : 2);
                $genderRankB = strtolower($b->gender ?? '') === 'male' ? 0 : (strtolower($b->gender ?? '') === 'female' ? 1 : 2);

                if ($genderRankA !== $genderRankB) {
                    return $genderRankA <=> $genderRankB;
                }

                return strcasecmp($a->name, $b->name);
            })
            ->values();

        // If Senior High (Grade 11 & Grade 12), group by pathway in alphabetical order
        $pathwayGroups = collect();
        if ($isSeniorHigh && $students->isNotEmpty()) {
            $pathwayGroups = $students->groupBy(function ($student) {
                $pathway = trim($student->student_record->pathway ?? $student->student_enrollment->pathway ?? '');
                return $pathway ?: 'Unassigned Pathway';
            })->sortKeys();
        }

        return view('pages.super_admin.classes_students.show', compact('my_class', 'advisor', 'students', 'isSeniorHigh', 'pathwayGroups', 'isStudent'));
    }
}
