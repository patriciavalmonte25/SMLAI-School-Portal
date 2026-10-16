<?php

namespace App\Http\Controllers;


use App\Helpers\Qs;
use App\Http\Requests\UserChangePass;
use App\Http\Requests\UserUpdate;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\Repositories\UserRepo;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MyAccountController extends Controller
{
    protected $user;

    public function __construct(UserRepo $user)
    {
        $this->user = $user;
    }

    public function edit_profile()
    {
        if (Auth::user()->user_type === 'enroll') {
            return redirect()->route('pending_submissions')->with('flash_warning', 'Temporary accounts do not have access to this page.');
        }

        $d['my'] = Auth::user();
        $d['student_record'] = StudentRecord::with(['my_class', 'section'])
            ->where('user_id', $d['my']->id)
            ->first();
        $d['student_enrollment'] = StudentEnrollment::with(['my_class', 'section'])
            ->where('enrolled_student_user_id', $d['my']->id)
            ->first();
        $d['parent_children'] = collect();
        if($d['my']->user_type === 'parent') {
            $childIds = $d['my']->children()->pluck('users.id')
                ->merge(StudentRecord::where('my_parent_id', $d['my']->id)->pluck('user_id'))
                ->unique()
                ->values();
            $d['parent_children'] = User::whereIn('id', $childIds)
                ->where('user_type', 'student')
                ->with(['student_record.my_class', 'student_enrollment.my_class'])
                ->orderBy('name')
                ->get();
        }
        return view('pages.support_team.my_account', $d);
    }

    public function update_profile(UserUpdate $req)
    {
        if (Auth::user()->user_type === 'enroll') {
            return redirect()->route('pending_submissions')->with('flash_warning', 'Temporary accounts do not have access to this page.');
        }

        $user = Auth::user();

        $d = $user->username ? $req->only(['email', 'phone', 'address']) : $req->only(['email', 'phone', 'address', 'username']);

        if(!$user->username && !$req->username && !$req->email){
            return back()->with('pop_error', __('msg.user_invalid'));
        }

        $user_type = $user->user_type;
        $code = $user->code;

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type).$code, $f['name']);
            $d['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($user->id, $d);
        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function change_pass(UserChangePass $req)
    {
        if (Auth::user()->user_type === 'enroll') {
            return redirect()->route('pending_submissions')->with('flash_warning', 'Temporary accounts cannot change their password.');
        }

        $user_id = Auth::user()->id;
        $my_pass = Auth::user()->password;
        $old_pass = $req->current_password;
        $new_pass = $req->password;

        if(password_verify($old_pass, $my_pass)){
            $data['password'] = Hash::make($new_pass);
            $this->user->update($user_id, $data);
            return back()->with('flash_success', __('msg.p_reset'));
        }

        return back()->with('flash_danger', __('msg.p_reset_fail'));
    }

}
