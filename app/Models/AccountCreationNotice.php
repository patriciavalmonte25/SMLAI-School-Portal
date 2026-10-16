<?php

namespace App\Models;

use Eloquent;

class AccountCreationNotice extends Eloquent
{
    protected $fillable = [
        'student_enrollment_id',
        'accounts',
        'sent_by',
        'acknowledged_at',
        'temporary_account_delete_at',
    ];

    protected $casts = [
        'accounts' => 'array',
        'acknowledged_at' => 'datetime',
        'temporary_account_delete_at' => 'datetime',
    ];

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(\App\User::class, 'sent_by');
    }

    public function recipients()
    {
        return $this->hasMany(AccountCreationNoticeRecipient::class, 'account_creation_notice_id');
    }
}
