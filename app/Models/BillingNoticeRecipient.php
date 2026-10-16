<?php

namespace App\Models;

use Eloquent;

class BillingNoticeRecipient extends Eloquent
{
    protected $fillable = ['billing_notice_id', 'user_id', 'parent_name'];

    public function notice()
    {
        return $this->belongsTo(BillingNotice::class, 'billing_notice_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}