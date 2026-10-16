<?php

namespace App\Models;

use Eloquent;

class BillingNotice extends Eloquent
{
    protected $fillable = [
        'student_user_id',
        'student_record_id',
        'section_id',
        'student_name',
        'student_class',
        'student_section',
        'layout',
        'sent_by',
        'paid_at',
    ];

    protected $casts = [
        'layout' => 'array',
        'paid_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_user_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(\App\User::class, 'sent_by');
    }

    public function recipients()
    {
        return $this->hasMany(BillingNoticeRecipient::class);
    }
}