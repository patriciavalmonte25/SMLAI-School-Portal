<?php

namespace App\Models;

use Eloquent;

class StudentGradeSheet extends Eloquent
{
    protected $fillable = [
        'student_user_id',
        'layout',
        'visibility',
    ];

    protected $casts = [
        'layout' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_user_id');
    }
}
