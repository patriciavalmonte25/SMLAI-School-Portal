<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Models\StudentEnrollment;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\User;
use Illuminate\Support\Facades\Auth;

class AjaxController extends Controller
{
    protected $loc, $my_class, $student;

    public function __construct(LocationRepo $loc, MyClassRepo $my_class, StudentRepo $student)
    {
        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->student = $student;
    }

    public function get_lga($state_id)
    {
//        $state_id = Qs::decodeHash($state_id);
//        return ['id' => Qs::hash($q->id), 'name' => $q->name];

        $lgas = $this->loc->getLGAs($state_id);
        return $data = $lgas->map(function($q){
            return ['id' => $q->id, 'name' => $q->name];
        })->all();
    }

    public function get_class_sections($class_id)
    {
        $sections = $this->my_class->getClassSections($class_id);
        return $sections = $sections->map(function($q){
            return ['id' => $q->id, 'name' => $q->name];
        })->all();
    }

    public function get_class_subjects($class_id)
    {
        $sections = $this->my_class->getClassSections($class_id);
        $subjects = $this->my_class->findSubjectByClass($class_id);

        if(Qs::userIsTeacher()){
            $subjects = $this->my_class->findSubjectByTeacher(Auth::user()->id)->where('my_class_id', $class_id);
        }

        $d['sections'] = $sections->map(function($q){
            return ['id' => $q->id, 'name' => $q->name];
        })->all();
        $d['subjects'] = $subjects->map(function($q){
            return ['id' => $q->id, 'name' => $q->name];
        })->all();

        return $d;
    }

    public function get_class_students($class_id)
    {
        $studentRecords = $this->student->findStudentsByClass($class_id);
        $enrolledStudentIds = StudentEnrollment::where('my_class_id', $class_id)
            ->whereNotNull('enrolled_student_user_id')
            ->pluck('enrolled_student_user_id');

        $studentIds = $studentRecords->pluck('user_id')
            ->concat($enrolledStudentIds)
            ->unique()
            ->filter();

        return User::whereIn('id', $studentIds)
            ->where('user_type', 'student')
            ->get()
            ->sortBy('name')
            ->map(function ($user) {
                return ['id' => Qs::hash($user->id), 'name' => $user->name];
            })
            ->values()
            ->all();
    }

}
