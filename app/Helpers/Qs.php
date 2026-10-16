<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\User;
use Hashids\Hashids;
use Illuminate\Support\Facades\Auth;

class Qs
{
    public static function displayError($errors)
    {
        foreach ($errors as $err) {
            $data[] = $err;
        }
        return '
                <div class="alert alert-danger alert-styled-left alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                    <span class="font-weight-semibold">Oops!</span> '.
        implode(' ', $data).'
                                </div>
                ';
    }

    public static function getAppCode()
    {
        return self::getSetting('system_title') ?: 'CJ';
    }

    public static function getDefaultUserImage()
    {
        return asset('global_assets/images/user.png');
    }

    public static function getPanelOptions()
    {
        return '';
    }

    public static function displaySuccess($msg)
    {
        return '
 <div class="alert alert-success alert-bordered">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button> '.
        $msg.'  </div>
                ';
    }

    public static function getTeamSA()
    {
        return ['admin', 'administrator'];
    }

    public static function getTeamAccount()
    {
        return ['admin', 'administrator', 'accountant'];
    }

    public static function getTeamSAT()
    {
        return ['admin', 'administrator', 'teacher'];
    }

    public static function getTeamAcademic()
    {
        return ['admin', 'administrator', 'teacher', 'student'];
    }

    public static function getTeamAdministrative()
    {
        return ['admin', 'administrator', 'accountant'];
    }

    public static function hash($id)
    {
        $hash = new Hashids('lav_sms_profile_links_v1', 14);
        return $hash->encode($id);
    }

    public static function getUserRecord($remove = [])
    {
        $data = ['name', 'email', 'phone', 'phone2', 'dob', 'gender', 'address', 'bg_id', 'nal_id', 'state_id', 'lga_id'];

        return $remove ? array_values(array_diff($data, $remove)) : $data;
    }

    public static function formatNameFromParts($lastName, $firstNames, $middleName = null)
    {
        $last = trim((string) $lastName);
        $first = trim((string) $firstNames);
        $middle = trim((string) ($middleName ?? ''));

        $firstMiddle = trim($first.' '.$middle);

        if($last && $firstMiddle){
            return $last.', '.$firstMiddle;
        }

        return $last ?: $firstMiddle;
    }

    public static function formatNameString($name)
    {
        $value = trim((string) $name);

        if(!$value){
            return $value;
        }

        $value = preg_replace('/\s+/', ' ', $value);

        // Keep already formatted names in LAST, FIRST MIDDLE shape.
        if(strpos($value, ',') !== false){
            [$last, $rest] = array_pad(explode(',', $value, 2), 2, '');
            return self::formatNameFromParts($last, $rest);
        }

        $parts = explode(' ', $value);

        if(count($parts) === 1){
            return $parts[0];
        }

        $last = array_pop($parts);
        $first = array_shift($parts);
        $middle = implode(' ', $parts);

        return self::formatNameFromParts($last, $first, $middle);
    }

    public static function getStaffRecord($remove = [])
    {
        $data = ['emp_date',];

        return $remove ? array_values(array_diff($data, $remove)) : $data;
    }

    public static function getStudentData($remove = [])
    {
        $data = ['my_class_id', 'section_id', 'my_parent_id', 'year_admitted', 'house', 'age'];

        return $remove ? array_values(array_diff($data, $remove)) : $data;

    }

    public static function decodeHash($str, $toString = true)
    {
        $hash = new Hashids('lav_sms_profile_links_v1', 14);
        $decoded = $hash->decode($str);
        return $toString ? implode(',', $decoded) : $decoded;
    }

    public static function userIsTeamAccount()
    {
        return in_array(Auth::user()->user_type, self::getTeamAccount());
    }

    public static function userIsTeamSA()
    {
        return in_array(Auth::user()->user_type, self::getTeamSA());
    }

    public static function userIsTeamSAT()
    {
        return in_array(Auth::user()->user_type, self::getTeamSAT());
    }

    public static function userIsAcademic()
    {
        return in_array(Auth::user()->user_type, self::getTeamAcademic());
    }

    public static function userIsAdministrative()
    {
        return in_array(Auth::user()->user_type, self::getTeamAdministrative());
    }

    public static function userIsAdmin()
    {
        return Auth::user()->user_type == 'admin';
    }

    public static function getUserType()
    {
        return Auth::user()->user_type;
    }

    public static function userIsAdministrator()
    {
        return Auth::user()->user_type == 'administrator';
    }

    public static function userIsAccountant()
    {
        return Auth::user()->user_type == 'accountant';
    }

    public static function userIsRegistrar()
    {
        return Auth::user()->user_type == 'registrar';
    }

    public static function userIsStudent()
    {
        return Auth::user()->user_type == 'student';
    }

    public static function userIsTeacher()
    {
        return Auth::user()->user_type == 'teacher';
    }

    public static function userIsParent()
    {
        return Auth::user()->user_type == 'parent';
    }

    public static function userIsStaff()
    {
        return in_array(Auth::user()->user_type, self::getStaff());
    }

    public static function getStaff($remove=[])
    {
        $data =  ['administrator', 'registrar', 'admin', 'teacher', 'accountant', 'librarian'];
        return $remove ? array_values(array_diff($data, $remove)) : $data;
    }

    public static function getAllUserTypes($remove=[])
    {
        $data =  ['administrator', 'registrar', 'admin', 'teacher', 'accountant', 'librarian', 'student', 'parent'];
        return $remove ? array_values(array_diff($data, $remove)) : $data;
    }

    // Check if User is the primary administrator (untouchable).
    public static function headSA(int $user_id)
    {
        return $user_id === 1;
    }

    public static function userIsPTA()
    {
        return in_array(Auth::user()->user_type, self::getPTA());
    }

    public static function userIsMyChild($student_id, $parent_id)
    {
        $data = ['user_id' => $student_id, 'my_parent_id' =>$parent_id];
        if(StudentRecord::where($data)->exists()) {
            return true;
        }

        $student = User::whereKey($student_id)->first();
        return $student && $student->parents()->where('users.id', $parent_id)->exists();
    }

    public static function getSRByUserID($user_id)
    {
        return StudentRecord::where('user_id', $user_id)->first();
    }

    public static function getPTA()
    {
        return ['administrator', 'registrar', 'admin', 'teacher', 'parent'];
    }

    /*public static function filesToUpload($programme)
    {
        return ['birth_cert', 'passport',  'neco_cert', 'waec_cert', 'ref1', 'ref2'];
    }*/

    public static function getPublicUploadPath()
    {
        return 'uploads/';
    }

    public static function getUserUploadPath()
    {
        return 'uploads/'.date('Y').'/'.date('m').'/'.date('d').'/';
    }

    public static function getUploadPath($user_type)
    {
        return 'uploads/'.$user_type.'/';
    }

    public static function getFileMetaData($file)
    {
        //$dataFile['name'] = $file->getClientOriginalName();
        $dataFile['ext'] = $file->getClientOriginalExtension();
        $dataFile['type'] = $file->getClientMimeType();
        $dataFile['size'] = self::formatBytes($file->getSize());
        return $dataFile;
    }

    public static function generateUserCode()
    {
        return substr(uniqid(mt_rand()), -7, 7);
    }

    public static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public static function getSetting($type)
    {
        $setting = Setting::where('type', $type)->first();
        return $setting ? $setting->description : '';
    }

    public static function getCurrentSession()
    {
        $value = self::getSetting('current_session');
        return $value ?: date('Y');
    }

    public static function getNextSession()
    {
        $oy = self::getCurrentSession();
        $old_yr = explode('-', $oy);
        return ++$old_yr[0].'-'.++$old_yr[1];
    }

    public static function getSystemName()
    {
        return self::getSetting('system_name');
    }

    public static function findMyChildren($parent_id)
    {
        $studentIds = User::whereKey($parent_id)->first()
            ? User::find($parent_id)->children()->pluck('users.id')
            : collect();

        return StudentRecord::where(function ($query) use ($parent_id, $studentIds) {
            $query->where('my_parent_id', $parent_id);
            if($studentIds->isNotEmpty()) {
                $query->orWhereIn('user_id', $studentIds);
            }
        })->with(['user', 'my_class'])->get();
    }

    public static function findTeacherSubjects($teacher_id)
    {
        return Subject::where('teacher_id', $teacher_id)->with('my_class')->get();
    }

    public static function findStudentRecord($user_id)
    {
        return StudentRecord::where('user_id', $user_id)->first();
    }

    public static function getMarkType($class_type)
    {
       switch($class_type){
           case 'J' : return 'junior';
           case 'S' : return 'senior';
           case 'N' : return 'nursery';
           case 'P' : return 'primary';
           case 'PN' : return 'pre_nursery';
           case 'C' : return 'creche';
       }
        return $class_type;
    }

    public static function json($msg, $ok = TRUE, $arr = [])
    {
        return $arr ? response()->json($arr) : response()->json(['ok' => $ok, 'msg' => $msg]);
    }

    public static function jsonStoreOk()
    {
        return self::json(__('msg.store_ok'));
    }

    public static function jsonUpdateOk()
    {
        return self::json(__('msg.update_ok'));
    }

    public static function storeOk($routeName)
    {
        return self::goWithSuccess($routeName, __('msg.store_ok'));
    }

    public static function deleteOk($routeName)
    {
        return self::goWithSuccess($routeName, __('msg.del_ok'));
    }

    public static function updateOk($routeName)
    {
        return self::goWithSuccess($routeName, __('msg.update_ok'));
    }

    public static function goToRoute($goto, $status = 302, $headers = [], $secure = null)
    {
        $data = [];
        $to = (is_array($goto) ? $goto[0] : $goto) ?: 'dashboard';
        if(is_array($goto)){
            array_shift($goto);
            $data = $goto;
        }
        return app('redirect')->to(route($to, $data), $status, $headers, $secure);
    }

    public static function goWithDanger($to = 'dashboard', $msg = NULL)
    {
        $msg = $msg ? $msg : __('msg.rnf');
        return self::goToRoute($to)->with('flash_danger', $msg);
    }

    public static function goWithSuccess($to, $msg)
    {
        return self::goToRoute($to)->with('flash_success', $msg);
    }

    public static function getDaysOfTheWeek()
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    }

}
