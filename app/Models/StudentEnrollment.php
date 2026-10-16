<?php

namespace App\Models;

use App\Helpers\Qs;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentEnrollment extends Eloquent
{
    use HasFactory;

    protected $appends = ['formatted_name'];

    protected $casts = [
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'account_created_notice_at' => 'datetime',
        'account_created_acknowledged_at' => 'datetime',
        'temporary_account_delete_at' => 'datetime',
    ];

    protected $fillable = [
        'surname',
        'first_name',
        'middle_name',
        'dob',
        'place_of_birth_house',
        'place_of_birth_street',
        'place_of_birth_barangay',
        'place_of_birth_town',
        'place_of_birth_province',
        'gender',
        'religion',
        'home_house',
        'home_street',
        'home_barangay',
        'home_town',
        'home_province',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'guardian_name',
        'guardian_occupation',
        'guardian_relation',
        'contact_number',
        'last_school_name',
        'last_school_type',
        'last_school_address',
        'grade_enrolling',
        'my_class_id',
        'section_id',
        'pathway',
        'student_id',
        'account_username',
        'password',
        'status',
        'user_id',
        'approved_by',
        'approved_at',
        'denied_by',
        'denied_reason',
        'denied_at',
        'enrolled_student_user_id',
        'enrolled_at',
        'created_student_username',
        'account_created_notice_at',
        'account_created_acknowledged_at',
        'temporary_account_delete_at',
    ];

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function enrolledStudent()
    {
        return $this->belongsTo(\App\User::class, 'enrolled_student_user_id');
    }

    public function accounts()
    {
        return $this->belongsToMany(\App\User::class, 'student_enrollment_accounts')
            ->withPivot('account_type')
            ->withTimestamps();
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function deniedBy()
    {
        return $this->belongsTo(\App\User::class, 'denied_by');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function getFormattedNameAttribute()
    {
        return Qs::formatNameFromParts($this->surname, $this->first_name, $this->middle_name);
    }

    public function getStudentGradeAttribute()
    {
        return $this->attributes['grade_enrolling'] ?? null;
    }

    public function setStudentGradeAttribute($value)
    {
        $this->attributes['grade_enrolling'] = $value;
    }
}
