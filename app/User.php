<?php

namespace App;

use App\Helpers\Qs;
use App\Models\BloodGroup;
use App\Models\Lga;
use App\Models\Nationality;
use App\Models\StaffRecord;
use App\Models\State;
use App\Models\StudentRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'phone', 'phone2', 'dob', 'gender', 'photo', 'address', 'bg_id', 'password', 'nal_id', 'state_id', 'lga_id', 'code', 'user_type', 'roles', 'email_verified_at'
    ];

    protected $casts = [
        'roles' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function student_record()
    {
        return $this->hasOne(StudentRecord::class);
    }

    public function children()
    {
        return $this->belongsToMany(self::class, 'parent_student_links', 'parent_user_id', 'student_user_id')
            ->withTimestamps();
    }

    public function parents()
    {
        return $this->belongsToMany(self::class, 'parent_student_links', 'student_user_id', 'parent_user_id')
            ->withTimestamps();
    }

    public function student_enrollment()
    {
        return $this->hasOne(\App\Models\StudentEnrollment::class, 'enrolled_student_user_id');
    }

    public function gradeSheet()
    {
        return $this->hasOne(\App\Models\StudentGradeSheet::class, 'student_user_id');
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nal_id');
    }

    public function blood_group()
    {
        return $this->belongsTo(BloodGroup::class, 'bg_id');
    }

    public function staff()
    {
        return $this->hasMany(StaffRecord::class);
    }

    public function getNameAttribute($value)
    {
        return Qs::formatNameString($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = Qs::formatNameString($value);
    }
}
