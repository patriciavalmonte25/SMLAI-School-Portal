<?php

namespace App\Models;

use Eloquent;

class BillingList extends Eloquent
{
    protected $fillable = ['grade_key', 'grade_label', 'layout'];
}
