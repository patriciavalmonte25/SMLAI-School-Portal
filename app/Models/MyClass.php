<?php

namespace App\Models;

use Eloquent;

class MyClass extends Eloquent
{
    protected $fillable = ['name', 'code', 'class_type_id', 'adviser_user_id'];

    public function adviser()
    {
        return $this->belongsTo(\App\User::class, 'adviser_user_id');
    }

    public function scopeSchoolOrder($query)
    {
        return $query
            ->orderByRaw("CASE
                WHEN UPPER(name) REGEXP '^[[:space:]]*(KINDERGARTEN|KINDER)([^A-Z0-9]|$)' THEN 0
                WHEN UPPER(name) REGEXP '^[[:space:]]*K([0-9]*)([^A-Z0-9]|$)' THEN 0
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?1([^0-9]|$)' THEN 1
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?2([^0-9]|$)' THEN 2
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?3([^0-9]|$)' THEN 3
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?4([^0-9]|$)' THEN 4
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?5([^0-9]|$)' THEN 5
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?6([^0-9]|$)' THEN 6
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?7([^0-9]|$)' THEN 7
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?8([^0-9]|$)' THEN 8
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?9([^0-9]|$)' THEN 9
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?10([^0-9]|$)' THEN 10
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?11([^0-9]|$)' THEN 11
                WHEN name REGEXP '^[[:space:]]*(GRADE[[:space:]]*)?12([^0-9]|$)' THEN 12
                ELSE 99
            END")
            ->orderBy('name', 'asc');
    }

    public function section()
    {
        return $this->hasMany(Section::class);
    }

    public function class_type()
    {
        return $this->belongsTo(ClassType::class);
    }

    public function student_record()
    {
        return $this->hasMany(StudentRecord::class);
    }
}
