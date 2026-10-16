<?php

namespace App\Models;

use App\User;
use Eloquent;

class TimeTableRecord extends Eloquent
{
    protected $fillable = ['name', 'target_type', 'my_class_id', 'owner_user_id', 'exam_id', 'year', 'timetable_layout'];

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
