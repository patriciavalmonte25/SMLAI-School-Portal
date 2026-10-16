<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Http\Request;

class SchoolStatisticsController extends Controller
{
    public function index(Request $request, $type = 'students')
    {
        $type = $type === 'super_admins' ? 'administrators' : $type;
        $allowedTypes = ['students', 'parents', 'teachers', 'administrators'];
        $typeLabels = [
            'students' => 'Students',
            'parents' => 'Parents',
            'teachers' => 'Teachers',
            'administrators' => 'Administrators',
        ];

        if(!in_array($type, $allowedTypes, true)){
            abort(404);
        }

        $students = User::where('user_type', 'student')
            ->with(['student_record.my_class', 'student_enrollment.my_class'])
            ->orderBy('name', 'asc')
            ->get();

        $parents = User::where('user_type', 'parent')
            ->orderBy('name', 'asc')
            ->get();

        $teachers = User::where('user_type', 'teacher')
            ->orderBy('name', 'asc')
            ->get();

        $administrators = User::where('user_type', 'administrator')
            ->orderBy('name', 'asc')
            ->get();

        $classSummaries = StudentRecord::query()
            ->join('users', 'users.id', '=', 'student_records.user_id')
            ->leftJoin('my_classes', 'my_classes.id', '=', 'student_records.my_class_id')
            ->where('student_records.grad', 0)
            ->where('users.user_type', 'student')
            ->selectRaw('student_records.my_class_id, COUNT(*) as total_students, SUM(CASE WHEN LOWER(users.gender) = ? THEN 1 ELSE 0 END) as boys_count, SUM(CASE WHEN LOWER(users.gender) = ? THEN 1 ELSE 0 END) as girls_count', ['male', 'female'])
            ->groupBy('student_records.my_class_id')
            ->get()
            ->keyBy('my_class_id');

        $classes = MyClass::schoolOrder()->get();

        $parentChildren = StudentRecord::whereIn('my_parent_id', $parents->pluck('id'))
            ->with(['my_class', 'section'])
            ->orderBy('my_parent_id', 'asc')
            ->get()
            ->groupBy('my_parent_id');

        $parentGradeSections = [];
        foreach($parents as $parent){
            $children = $parentChildren->get($parent->id, collect());
            $gradeSections = $children->map(function($sr){
                return trim($sr->my_class->name ?? 'N/A');
            })->filter()->unique()->values()->implode(', ');

            $parentGradeSections[$parent->id] = $gradeSections ?: 'No linked child records';
        }

        return view('pages.super_admin.school_statistics.index', [
            'activeType' => $type,
            'activeTypeLabel' => $typeLabels[$type] ?? ucfirst($type),
            'students' => $students,
            'parents' => $parents,
            'teachers' => $teachers,
            'administrators' => $administrators,
            'classes' => $classes,
            'classSummaries' => $classSummaries,
            'parentGradeSections' => $parentGradeSections,
        ]);
    }
}
