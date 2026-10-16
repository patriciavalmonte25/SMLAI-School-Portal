<?php

namespace App\Models;

use Eloquent;

class DocumentRequest extends Eloquent
{
    protected $fillable = [
        'requester_user_id',
        'student_user_id',
        'student_name',
        'school_year_last_attended',
        'grade_section',
        'request_items',
        'form_137_grade',
        'form_138_grade',
        'other_request',
        'purpose',
        'information_name',
        'date_of_birth',
        'place_of_birth',
        'contact_messenger',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'parents_address',
        'status',
        'release_date',
        'fee',
        'rejection_reason',
        'request_received_by',
        'request_prepared_by',
        'request_released_by',
        'collection_date',
        'reviewed_by',
    ];

    protected $casts = [
        'request_items' => 'array',
        'date_of_birth' => 'date',
        'release_date' => 'date',
        'collection_date' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(\App\User::class, 'requester_user_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(\App\User::class, 'reviewed_by');
    }
}