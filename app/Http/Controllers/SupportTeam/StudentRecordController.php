<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\Mk;
use App\User;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\Http\Requests\Student\StudentRecordCreate;
use App\Http\Requests\Student\StudentRecordUpdate;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentRecordController extends Controller
{
    protected $loc, $my_class, $user, $student;

   public function __construct(LocationRepo $loc, MyClassRepo $my_class, UserRepo $user, StudentRepo $student)
   {
       $this->middleware(function ($request, $next) {
           if (Qs::userIsAdministrator()) {
               return Qs::goToRoute(['dashboard']);
           }

           return $next($request);
       })->only(['create', 'store', 'listByClass', 'graduated', 'edit', 'update', 'destroy', 'not_graduated', 'reset_pass']);

    $this->middleware('teamSAOrRegistrar', ['only' => ['edit','update', 'reset_pass', 'create', 'store', 'graduated'] ]);
       $this->middleware('administrator', ['only' => ['destroy',] ]);

        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->user = $user;
        $this->student = $student;
   }

    public function reset_pass($st_id)
    {
        $st_id = Qs::decodeHash($st_id);
        $data['password'] = Hash::make('student');
        $this->user->update($st_id, $data);
        return back()->with('flash_success', __('msg.p_reset'));
    }

    public function create()
    {
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        $data['blood_groups'] = $this->user->getBloodGroups();
        $data['genders'] = ['Male', 'Female'];
        return view('pages.support_team.students.add', $data);
    }

    public function store(StudentRecordCreate $req)
    {
       $data =  $req->only(Qs::getUserRecord());
       $sr =  $req->only(Qs::getStudentData());

        $ct = $this->my_class->findTypeByClass($req->my_class_id)->code;
       /* $ct = ($ct == 'J') ? 'JSS' : $ct;
        $ct = ($ct == 'S') ? 'SS' : $ct;*/

        $data['user_type'] = 'student';
        $data['name'] = ucwords($req->name);
        $data['code'] = strtoupper(Str::random(10));
        $data['password'] = Hash::make('student');
        $data['photo'] = Qs::getDefaultUserImage();
        $adm_no = $req->adm_no;
        $data['username'] = strtoupper(Qs::getAppCode().'/'.$ct.'/'.$sr['year_admitted'].'/'.($adm_no ?: mt_rand(1000, 99999)));

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $user = $this->user->create($data); // Create User

        $sr['adm_no'] = $data['username'];
        $sr['user_id'] = $user->id;
        $sr['session'] = Qs::getCurrentSession();

        $this->student->createRecord($sr); // Create Student
        if(!empty($sr['my_parent_id'])) {
            $user->parents()->syncWithoutDetaching([$sr['my_parent_id']]);
        }
        return Qs::jsonStoreOk();
    }

    public function listByClass($class_id)
    {
        $data['my_class'] = $mc = $this->my_class->getMC(['id' => $class_id])->with('adviser')->first();
        $data['students'] = $this->student->findStudentsByClass($class_id);
        $data['sections'] = $this->my_class->getClassSections($class_id);
        $data['advisor'] = $mc ? ($mc->adviser ?: $mc->section->firstWhere('teacher_id')?->teacher) : null;

        return is_null($mc) ? Qs::goWithDanger() : view('pages.support_team.students.list', $data);
    }

    public function graduated()
    {
        $data['my_classes'] = $this->my_class->all();
        $data['students'] = $this->student->allGradStudents();

        return view('pages.support_team.students.graduated', $data);
    }

    public function registrarIndex()
    {
        $students = User::where('user_type', 'student')
            ->with(['student_record.my_class', 'student_record.section', 'student_enrollment.my_class'])
            ->orderBy('name', 'asc')
            ->get();

        return view('pages.registrar.students.index', compact('students'));
    }

    public function deleteSelectedStudents(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $students = User::whereIn('id', $data['user_ids'])
            ->where('user_type', 'student')
            ->get();

        foreach ($students as $student) {
            $path = Qs::getUploadPath('student').$student->code;
            Storage::exists($path) ? Storage::deleteDirectory($path) : false;
            $this->user->delete($student->id);
        }

        return back()->with('flash_success', $students->count().' student accounts deleted successfully.');
    }

    public function not_graduated($sr_id)
    {
        $d['grad'] = 0;
        $d['grad_date'] = NULL;
        $d['session'] = Qs::getCurrentSession();
        $this->student->updateRecord($sr_id, $d);

        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function show($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();
        $isStaffProfileViewer = in_array(Auth::user()->user_type, ['admin', 'administrator', 'teacher', 'accountant', 'registrar']);
        $data['can_view_parent_links'] = $isStaffProfileViewer;
        $data['parents_guardians'] = collect();
        if($isStaffProfileViewer) {
            $parentIds = $data['sr']->user->parents()->pluck('users.id')
                ->merge(StudentRecord::where('user_id', $data['sr']->user_id)
                    ->whereNotNull('my_parent_id')
                    ->pluck('my_parent_id'));
            $enrollments = StudentEnrollment::where(function ($query) use ($data) {
                $query->where('enrolled_student_user_id', $data['sr']->user_id)
                    ->orWhere('user_id', $data['sr']->user_id);
            })->get();
            foreach($enrollments as $enrollment) {
                $parentIds = $parentIds->merge(
                    $enrollment->accounts()->wherePivot('account_type', 'Parent')->pluck('users.id')
                );
            }
            $data['parents_guardians'] = User::whereIn('id', $parentIds->unique()->values())
                ->where('user_type', 'parent')
                ->orderBy('name')
                ->get();
        }

        /* Prevent Other Students/Parents from viewing Profile of others */
        if(Auth::user()->id != $data['sr']->user_id && !$isStaffProfileViewer && !Qs::userIsMyChild($data['sr']->user_id, Auth::user()->id)){
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        return view('pages.support_team.students.show', $data);
    }

    public function edit($sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $data['sr'] = $this->student->getRecord(['id' => $sr_id])->first();
        $data['my_classes'] = $this->my_class->all();
        $data['parents'] = $this->user->getUserByType('parent');
        $data['states'] = $this->loc->getStates();
        $data['nationals'] = $this->loc->getAllNationals();
        $data['blood_groups'] = $this->user->getBloodGroups();
        $data['genders'] = ['Male', 'Female'];
        return view('pages.support_team.students.edit', $data);
    }

    public function update(StudentRecordUpdate $req, $sr_id)
    {
        $sr_id = Qs::decodeHash($sr_id);
        if(!$sr_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['id' => $sr_id])->first();
        $d =  $req->only(Qs::getUserRecord());
        $d['name'] = ucwords($req->name);
        if (Qs::userIsRegistrar() && $req->filled('username')) {
            $d['username'] = $req->username;
        }

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath('student').$sr->user->code, $f['name']);
            $d['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($sr->user->id, $d); // Update User Details

        $srec = $req->only(Qs::getStudentData());

        $this->student->updateRecord($sr_id, $srec); // Update St Rec
        if(array_key_exists('my_parent_id', $srec)) {
            $oldParentId = (int) $sr->my_parent_id;
            $newParentId = (int) $srec['my_parent_id'];
            if($oldParentId && $oldParentId !== $newParentId) {
                $sr->user->parents()->detach($oldParentId);
                StudentEnrollment::where(function ($query) use ($sr) {
                    $query->where('enrolled_student_user_id', $sr->user_id)
                        ->orWhere('user_id', $sr->user_id);
                })->get()->each(function ($enrollment) use ($oldParentId) {
                    $enrollment->accounts()->detach($oldParentId);
                });
            }
            if($newParentId) {
                $sr->user->parents()->syncWithoutDetaching([$newParentId]);
                StudentEnrollment::where(function ($query) use ($sr) {
                    $query->where('enrolled_student_user_id', $sr->user_id)
                        ->orWhere('user_id', $sr->user_id);
                })->get()->each(function ($enrollment) use ($newParentId) {
                    $enrollment->accounts()->syncWithoutDetaching([$newParentId => ['account_type' => 'Parent']]);
                });
            }
        }

        /*** If Class/Section is Changed in Same Year, Delete Marks/ExamRecord of Previous Class/Section ****/
        Mk::deleteOldRecord($sr->user->id, $srec['my_class_id']);

        return Qs::jsonUpdateOk();
    }

    public function destroy($st_id)
    {
        $st_id = Qs::decodeHash($st_id);
        if(!$st_id){return Qs::goWithDanger();}

        $sr = $this->student->getRecord(['user_id' => $st_id])->first();
        $path = Qs::getUploadPath('student').$sr->user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : false;
        $this->user->delete($sr->user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

}
