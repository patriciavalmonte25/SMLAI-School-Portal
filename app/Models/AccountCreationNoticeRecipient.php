<?php

namespace App\Models;

use Eloquent;

class AccountCreationNoticeRecipient extends Eloquent
{
    protected $fillable = [
        'account_creation_notice_id',
        'user_id',
        'account_type',
        'username',
    ];

    public function notice()
    {
        return $this->belongsTo(AccountCreationNotice::class, 'account_creation_notice_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
