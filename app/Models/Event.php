<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['date', 'event', 'created_by', 'visibility'];
    protected $dates = ['date'];
    protected $casts = ['visibility' => 'array'];
    protected $attributes = ['visibility' => '["administrator","registrar","accountant","teacher","student","parent"]'];

    public function creator()
    {
        return $this->belongsTo('App\User', 'created_by');
    }
}
