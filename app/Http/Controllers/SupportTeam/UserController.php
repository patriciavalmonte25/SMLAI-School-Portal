<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Mk;
use App\Helpers\Qs;
use App\Helpers\AccountCredentials;
use App\Http\Requests\UserRequest;
use App\User;
use App\Models\Lga;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\Models\MyClass;
use App\Models\Section;
use App\Repositories\LocationRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\StudentRepo;
use App\Repositories\UserRepo;
use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class UserController extends Controller
{
    protected $user, $loc, $my_class, $student;

    public function __construct(UserRepo $user, LocationRepo $loc, MyClassRepo $my_class, StudentRepo $student)
    {
        $this->middleware('registrar', ['only' => ['create', 'store', 'edit', 'update', 'reset_pass', 'destroy', 'deleteSelected']]);
        $this->middleware('administratorOrRegistrar', ['only' => ['index']]);

        $this->user = $user;
        $this->loc = $loc;
        $this->my_class = $my_class;
        $this->student = $student;
    }

    protected function getManagedUserTypes()
    {
        return $this->getCreatableUserTypes();
    }

    protected function getCreatableUserTypes()
    {
        $allowed = ['administrator', 'registrar', 'accountant', 'teacher', 'parent'];
        $types = UserType::whereIn('title', $allowed)->get();

        $ordered = collect();
        foreach($allowed as $title) {
            $type = $types->firstWhere('title', $title);
            if($type) {
                $ordered->push($type);
            }
        }

        return $ordered;
    }

    public function index()
    {
        $d['user_types'] = $this->getManagedUserTypes();
        $d['creatable_user_types'] = $this->getCreatableUserTypes();
        $d['users'] = $this->user->getPTAUsers();
        $parentUsers = $d['users']->where('user_type', 'parent');
        $parentIds = $parentUsers->pluck('id');
        $d['users']->load([
            'children.student_record.my_class',
            'children.student_record.section',
            'children.student_enrollment.my_class',
            'children.student_enrollment.section',
        ]);
        $legacyChildrenByParent = StudentRecord::whereIn('my_parent_id', $parentIds)
            ->with(['user.student_record.my_class', 'user.student_record.section', 'user.student_enrollment.my_class', 'user.student_enrollment.section'])
            ->get()
            ->groupBy('my_parent_id');
        $d['parent_children_by_parent'] = $parentUsers->mapWithKeys(function ($parent) use ($legacyChildrenByParent) {
            $legacyChildren = $legacyChildrenByParent->get($parent->id, collect())->pluck('user');
            $children = $parent->children
                ->merge($legacyChildren)
                ->filter()
                ->unique('id')
                ->sortBy(function ($student) {
                    $sectionId = optional($student->student_record)->section_id
                        ?: optional($student->student_enrollment)->section_id;

                    return sprintf('%010d-%s', $sectionId ?: 2147483647, strtolower($student->name));
                })
                ->values();

            return [$parent->id => $children];
        });

        return view('pages.support_team.users.index', $d);
    }

    public function create()
    {
        $d['creatable_user_types'] = $this->getCreatableUserTypes();
        $d['my_classes'] = $this->my_class->all();
        $d['states'] = $this->loc->getStates();
        $d['nationals'] = $this->loc->getAllNationals();
        $d['blood_groups'] = $this->user->getBloodGroups();
        $d['genders'] = ['Male', 'Female'];
        $d['temporary_enrollments'] = StudentEnrollment::with('user')
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
        $d['source_enrollment'] = null;
        if(request()->filled('enrollment_id')) {
            $d['source_enrollment'] = StudentEnrollment::whereKey(request('enrollment_id'))
                ->where('status', 'approved')
                ->firstOrFail();
        }
        $d['selected_temporary_enrollment_id'] = old('source_enrollment_id', optional($d['source_enrollment'])->id);

        $typeReq = strtolower(request('type', ''));
        $selected = null;

        if($typeReq){
            foreach($d['creatable_user_types'] as $type){
                $title = strtolower($type->title ?? '');
                $nameKey = strtolower(str_replace(' ', '_', $type->name ?? ''));

                if($title === $typeReq || $nameKey === $typeReq){
                    $selected = $type;
                    break;
                }
            }
        }

        $d['selected_user_type'] = $selected ?: $d['creatable_user_types']->first();

        return view('pages.support_team.users.create', $d);
    }

    public function edit($id)
    {
        $id = Qs::decodeHash($id);
        $d['user'] = $this->user->find($id);
        if(!$d['user']){
            abort(404, 'User profile not found. The profile link may have expired.');
        }
        $d['display_password'] = Qs::userIsRegistrar() ? $this->getDisplayPassword($d['user']) : null;
        $d['display_password'] = $this->getDisplayPassword($d['user']);
        $d['states'] = $this->loc->getStates();
        $d['lgas'] = Lga::orderBy('name', 'asc')->get();
        $d['users'] = $this->user->getPTAUsers();
        $d['blood_groups'] = $this->user->getBloodGroups();
        $d['nationals'] = $this->loc->getAllNationals();
        $d['genders'] = ['Male', 'Female'];
        $d['my_classes'] = $this->my_class->all();
        $d['adviser_class_id'] = MyClass::where('adviser_user_id', $id)->value('id');
        $d['student_record'] = null;
        $d['student_enrollment'] = null;
        $d['parent_children'] = [];

        if($d['user']->user_type === 'student'){
            $d['student_record'] = $this->student->findByUserId($id)->first();
            $d['sections'] = $d['student_record'] ? $this->my_class->getClassSections($d['student_record']->my_class_id) : collect();
            $d['student_enrollment'] = StudentEnrollment::where('enrolled_student_user_id', $id)->first() ?: new StudentEnrollment();
        }

        if($d['user']->user_type === 'parent'){
            $childIds = $d['user']->children()->pluck('users.id')
                ->merge(StudentRecord::where('my_parent_id', $id)->pluck('user_id'))
                ->unique()
                ->values();
            $d['parent_children'] = User::whereIn('id', $childIds)
                ->with(['student_record', 'student_enrollment'])
                ->get()
                ->map(function ($student) {
                    $studentRecord = $student->student_record;
                    $enrollment = $student->student_enrollment;
                    return [
                        'class_id' => optional($studentRecord)->my_class_id ?: optional($enrollment)->my_class_id,
                        'student_id' => Qs::hash($student->id),
                    ];
                })
                ->values()
                ->all();
        }

        return view('pages.support_team.users.edit', $d);
    }

    public function reset_pass($id)
    {
        // Prevent changes to the primary administrator account.
        if(Qs::headSA($id)){
            return back()->with('flash_danger', __('msg.denied'));
        }

        $resetPassword = 'PasswordReset2026!';
        $data['password'] = Hash::make($resetPassword);
        $this->user->update($id, $data);
        DB::table('users')->where('id', $id)->update(['password_encrypted' => encrypt($resetPassword)]);
        return back()->with('flash_success', __('msg.pu_reset'));
    }

    public function store(UserRequest $req)
    {
        $user_type = $this->user->findType($req->user_type)->title;

        if(!in_array($user_type, ['administrator', 'registrar', 'accountant', 'teacher', 'parent'])) {
            return Qs::json(__('msg.denied'), FALSE);
        }

        $sourceEnrollment = null;
        $isEnrollmentParentAccount = $user_type === 'parent' && $req->filled('source_enrollment_id');
        if($isEnrollmentParentAccount) {
            $sourceEnrollment = StudentEnrollment::whereKey($req->source_enrollment_id)
                ->where('status', 'approved')
                ->firstOrFail();
        }

        $data = $req->except(array_merge(Qs::getStaffRecord(), ['children', 'children_present', 'roles', 'source_enrollment_id', 'adviser_class_id']));
        $data['name'] = ucwords($req->name);
        $data['user_type'] = $user_type;
        $data['photo'] = Qs::getDefaultUserImage();
        $data['code'] = strtoupper(Str::random(10));

        if(in_array($user_type, ['administrator', 'teacher']) && $req->filled('roles')) {
            $roles = collect($req->roles)
                ->filter(function ($role) {
                    return trim((string) $role) !== '';
                })
                ->map(function ($role) {
                    return trim((string) $role);
                })
                ->values()
                ->all();

            if(!empty($roles)) {
                $data['roles'] = $roles;
            }
        }

        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_teamSA = in_array($user_type, Qs::getTeamSA()) || in_array($user_type, ['registrar', 'accountant']);

        $empDate = $req->emp_date ?: date('Y-m-d');
        $staff_id = Qs::getAppCode().'/STAFF/'.date('Y/m', strtotime($empDate)).'/'.mt_rand(1000, 9999);
        $data['username'] = $uname = ($user_is_teamSA || $isEnrollmentParentAccount) ? $req->username : $staff_id;

        $pass = AccountCredentials::randomPassword(8);
        $data['password'] = Hash::make($pass);

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type).$data['code'], $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        /* Ensure that both username and Email are not blank*/
        if(!$uname && !$req->email){
            return back()->with('pop_error', __('msg.user_invalid'));
        }

        $user = $this->user->create($data); // Create User
        $profileDetails = array_intersect_key($data, array_flip(['occupation', 'relationship_to_children']));
        $profileDetails['password_encrypted'] = encrypt($pass);
        DB::table('users')->where('id', $user->id)->update($profileDetails);

        if($user_type === 'teacher') {
            $this->syncTeacherAdviser($user->id, $req->input('adviser_class_id'));
        }

        if($sourceEnrollment) {
            $sourceEnrollment->accounts()->syncWithoutDetaching([$user->id => ['account_type' => 'Parent']]);

            if($sourceEnrollment->enrolled_student_user_id) {
                $user->children()->syncWithoutDetaching([$sourceEnrollment->enrolled_student_user_id]);
                StudentRecord::where('user_id', $sourceEnrollment->enrolled_student_user_id)
                    ->whereNull('my_parent_id')
                    ->update(['my_parent_id' => $user->id]);
            }
        }

        /* CREATE STAFF RECORD */
        if($user_is_staff){
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['user_id'] = $user->id;
            $d2['code'] = $staff_id;
            $this->user->createStaffRecord($d2);
        }

        /* LINK CHILDREN TO PARENT */
        if($user_type === 'parent' && is_array($req->children)) {
            $this->syncParentChildren($user, $req->children, false);
        }

        if($isEnrollmentParentAccount) {
            return Qs::json('Parent account created successfully.');
        }

        if($user_type === 'teacher') {
            return response()->json([
                'ok' => true,
                'msg' => 'Teacher account created successfully.',
                'url' => route('users.index', ['tab' => 'teacher']),
            ]);
        }

        return Qs::jsonStoreOk();
    }

    public function update(UserRequest $req, $id)
    {
        $id = Qs::decodeHash($id);

        // Prevent changes to the primary administrator account.
        if(Qs::headSA($id)){
            return Qs::json(__('msg.denied'), FALSE);
        }

        $user = $this->user->find($id);

        $user_type = $user->user_type;
        $user_is_staff = in_array($user_type, Qs::getStaff());
        $user_is_teamSA = in_array($user_type, Qs::getTeamSA()) || in_array($user_type, ['registrar', 'accountant']);

        $data = $req->except(array_merge(Qs::getStaffRecord(), ['children', 'children_present', 'adviser_class_id']));
        if($user_type === 'student'){
            $data['name'] = Qs::formatNameFromParts($req->surname, $req->first_name, $req->middle_name ?? null) ?: ucwords($req->name);
        } else {
            $data['name'] = ucwords($req->name);
        }
        $data['user_type'] = $user_type;
        $profileDetails = array_intersect_key($data, array_flip(['occupation', 'relationship_to_children']));

        if($user_is_staff && !$user_is_teamSA && !Qs::userIsRegistrar()){
            $data['username'] = Qs::getAppCode().'/STAFF/'.date('Y/m', strtotime($req->emp_date)).'/'.mt_rand(1000, 9999);
        }
        else {
            $data['username'] = $req->filled('username') ? $req->username : $user->username;
        }

        $passwordForDisplay = null;
        if($req->filled('password') && $req->password !== $user->password){
            $data['password'] = Hash::make($req->password);
            $passwordForDisplay = $req->password;
        } else {
            unset($data['password']);
        }

        if($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type).$user->code, $f['name']);
            $data['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($id, $data);   /* UPDATE USER RECORD */

        if($user_type === 'teacher' && $req->has('adviser_class_id')) {
            $this->syncTeacherAdviser($id, $req->input('adviser_class_id'));
        }

        if($profileDetails) {
            $profileDetails['updated_at'] = now();
            DB::table('users')->where('id', $id)->update($profileDetails);
        }
        if($passwordForDisplay !== null) {
            DB::table('users')->where('id', $id)->update([
                'password_encrypted' => encrypt($passwordForDisplay),
                'updated_at' => now(),
            ]);
        }

        if($user_type === 'parent' && ($req->has('children_present') || is_array($req->children))) {
            $this->syncParentChildren($user, is_array($req->children) ? $req->children : [], true);
        }

        /* UPDATE STAFF RECORD */
        if($user_is_staff){
            $d2 = $req->only(Qs::getStaffRecord());
            $d2['code'] = $data['username'];
            $this->user->updateStaffRecord(['user_id' => $id], $d2);
        }

        /* UPDATE STUDENT DATA */
        if($user_type === 'student'){
            $classId = $req->my_class_id;
            $sectionId = $req->section_id;
            if($classId && !$sectionId){
                $sec = Section::where('my_class_id', $classId)->first();
                if(!$sec){
                    $sec = Section::create(['name' => 'A', 'my_class_id' => $classId, 'active' => 1]);
                }
                $sectionId = $sec ? $sec->id : null;
            }

            $sr = $this->student->findByUserId($id)->first();

            if($sr){
                $srec = $req->only(['my_class_id', 'pathway', 'adm_no']);
                if($sectionId){
                    $srec['section_id'] = $sectionId;
                }
                if($req->filled('student_id') && empty($srec['adm_no'])){
                    $srec['adm_no'] = $req->student_id;
                }
                $this->student->updateRecord($sr->id, $srec);

                if($req->filled('my_class_id')){
                    Mk::deleteOldRecord($id, $req->my_class_id);
                }
            } elseif($req->filled('my_class_id')) {
                $srec = [
                    'user_id' => $id,
                    'my_class_id' => $req->my_class_id,
                    'section_id' => $sectionId,
                    'pathway' => $req->pathway ?? null,
                    'adm_no' => $req->adm_no ?: ($req->student_id ?: $user->username),
                    'session' => Qs::getCurrentSession(),
                    'year_admitted' => date('Y'),
                ];
                $this->student->createRecord($srec);
            }

            /* UPDATE ENROLLMENT PERSONAL INFORMATION */
            $enrollment = StudentEnrollment::firstOrNew(['enrolled_student_user_id' => $id]);
            $enrollmentData = $req->only([
                'surname', 'first_name', 'middle_name',
                'dob', 'place_of_birth_town', 'gender', 'religion', 'home_town',
                'father_name', 'father_occupation',
                'mother_name', 'mother_occupation',
                'guardian_name', 'guardian_occupation', 'guardian_relation',
                'contact_number',
                'last_school_name', 'last_school_type', 'last_school_address',
                'my_class_id', 'pathway', 'student_id'
            ]);
            if($sectionId){
                $enrollmentData['section_id'] = $sectionId;
            }
            $enrollmentData['grade_enrolling'] = $req->student_grade ?: $req->grade_enrolling;
            if($req->filled('password')){
                $enrollmentData['password'] = $req->password;
            }
            $enrollment->fill($enrollmentData);
            $enrollment->enrolled_student_user_id = $id;
            $enrollment->save();

            // Check if there is another student account with identical personal details
            $hasDuplicate = false;
            if(!empty($req->surname) && !empty($req->first_name) && !empty($req->dob)){
                $hasDuplicate = StudentEnrollment::where('enrolled_student_user_id', '!=', $id)
                    ->where('surname', $req->surname)
                    ->where('first_name', $req->first_name)
                    ->where('dob', $req->dob)
                    ->where(function($q) use ($req) {
                        if (!empty($req->middle_name)) {
                            $q->where('middle_name', $req->middle_name);
                        }
                    })
                    ->exists();
            }

            if($hasDuplicate){
                return Qs::json(__('msg.update_ok') . ' - Warning: Another student account shares the exact same personal information.');
            }
        }

        return Qs::jsonUpdateOk();
    }

    private function syncTeacherAdviser($teacherId, $classId)
    {
        MyClass::where('adviser_user_id', $teacherId)->update(['adviser_user_id' => null]);
        if($classId) {
            MyClass::whereKey($classId)->update(['adviser_user_id' => $teacherId]);
        }
    }

    private function syncParentChildren(User $parent, array $children, bool $replace)
    {
        $selectedStudentIds = collect($children)->map(function ($child) {
            $studentId = (int) Qs::decodeHash($child['student_id'] ?? '');
            $classId = (int) ($child['class_id'] ?? 0);
            if (!$studentId || !$classId || !User::where('user_type', 'student')->whereKey($studentId)->exists()) {
                return null;
            }

            $hasRecordInClass = StudentRecord::where('user_id', $studentId)
                ->where('my_class_id', $classId)
                ->exists();
            $hasEnrollmentInClass = StudentEnrollment::where('enrolled_student_user_id', $studentId)
                ->where('my_class_id', $classId)
                ->exists();

            return $hasRecordInClass || $hasEnrollmentInClass ? $studentId : null;
        })->filter()->unique()->values();

        $previousStudentIds = $parent->children()->pluck('users.id');
        if($replace) {
            $parent->children()->sync($selectedStudentIds->all());
            $legacyLinksToClear = StudentRecord::where('my_parent_id', $parent->id);
            if($selectedStudentIds->isNotEmpty()) {
                $legacyLinksToClear->whereNotIn('user_id', $selectedStudentIds);
            }
            $legacyLinksToClear->update(['my_parent_id' => null]);
        } else {
            $parent->children()->syncWithoutDetaching($selectedStudentIds->all());
        }

        $removedStudentIds = $replace ? $previousStudentIds->diff($selectedStudentIds) : collect();
        if($removedStudentIds->isNotEmpty()) {
            StudentRecord::whereIn('user_id', $removedStudentIds)
                ->where('my_parent_id', $parent->id)
                ->update(['my_parent_id' => null]);

            StudentEnrollment::where(function ($query) use ($removedStudentIds) {
                $query->whereIn('enrolled_student_user_id', $removedStudentIds)
                    ->orWhereIn('user_id', $removedStudentIds);
            })
                ->get()
                ->each(function ($enrollment) use ($parent) {
                    $enrollment->accounts()->detach($parent->id);
                });
        }

        if($selectedStudentIds->isNotEmpty()) {
            StudentRecord::whereIn('user_id', $selectedStudentIds)
                ->whereNull('my_parent_id')
                ->update(['my_parent_id' => $parent->id]);

            StudentEnrollment::where(function ($query) use ($selectedStudentIds) {
                $query->whereIn('enrolled_student_user_id', $selectedStudentIds)
                    ->orWhereIn('user_id', $selectedStudentIds);
            })
                ->get()
                ->each(function ($enrollment) use ($parent) {
                    $enrollment->accounts()->syncWithoutDetaching([$parent->id => ['account_type' => 'Parent']]);
                });
        }
    }

    public function show($user_id)
    {
        $user_id = Qs::decodeHash($user_id);
        if(!$user_id){return back();}

        $data['user'] = $this->user->find($user_id);
        $data['display_password'] = Qs::userIsRegistrar() ? $this->getDisplayPassword($data['user']) : null;
        $data['student_enrollment'] = StudentEnrollment::with(['my_class', 'section'])
            ->where('enrolled_student_user_id', $user_id)
            ->first();
        $data['student_record'] = StudentRecord::with(['my_class', 'section'])
            ->where('user_id', $user_id)
            ->first();
        $data['parent_children'] = collect();
        if($data['user']->user_type === 'parent') {
            $childIds = $data['user']->children()->pluck('users.id')
                ->merge(StudentRecord::where('my_parent_id', $user_id)->pluck('user_id'))
                ->unique()
                ->values();
            $data['parent_children'] = User::whereIn('id', $childIds)
                ->where('user_type', 'student')
                ->with(['student_record.my_class', 'student_record.section', 'student_enrollment.my_class', 'student_enrollment.section'])
                ->get()
                ->sortBy(function ($student) {
                    $sectionId = optional($student->student_record)->section_id
                        ?: optional($student->student_enrollment)->section_id;

                    return sprintf('%010d-%s', $sectionId ?: 2147483647, strtolower($student->name));
                })
                ->values();
        }

        $isStaffProfileViewer = in_array(Auth::user()->user_type, ['admin', 'administrator', 'teacher', 'accountant', 'registrar']);
        if(Auth::user()->id != $user_id && !$isStaffProfileViewer && !Qs::userIsMyChild($user_id, Auth::id())){
            return redirect(route('dashboard'))->with('pop_error', __('msg.denied'));
        }

        $data['can_view_parent_links'] = $isStaffProfileViewer;
        $data['parents_guardians'] = collect();
        if($data['user']->user_type === 'student' && $isStaffProfileViewer) {
            $parentIds = $data['user']->parents()->pluck('users.id')
                ->merge(StudentRecord::where('user_id', $user_id)->whereNotNull('my_parent_id')->pluck('my_parent_id'));
            $enrollments = StudentEnrollment::where(function ($query) use ($user_id) {
                $query->where('enrolled_student_user_id', $user_id)
                    ->orWhere('user_id', $user_id);
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

        return view('pages.support_team.users.show', $data);
    }

    protected function getDisplayPassword(User $user)
    {
        if(!$user->password_encrypted) {
            return null;
        }

        try {
            return decrypt($user->password_encrypted);
        } catch(\Throwable $exception) {
            return null;
        }
    }

    public function destroy($id)
    {
        $id = Qs::decodeHash($id);

        // Redirect if Making Changes to Head of Administrators
        if(Qs::headSA($id)){
            return back()->with('pop_error', __('msg.denied'));
        }

        $user = $this->user->find($id);

        if($user->user_type == 'teacher' && $this->userTeachesSubject($user)) {
            return back()->with('pop_error', __('msg.del_teacher'));
        }

        $path = Qs::getUploadPath($user->user_type).$user->code;
        Storage::exists($path) ? Storage::deleteDirectory($path) : true;
        $this->user->delete($user->id);

        return back()->with('flash_success', __('msg.del_ok'));
    }

    public function deleteSelected(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $users = User::whereIn('id', $data['user_ids'])->get();
        $deleted = 0;
        $skipped = 0;

        foreach($users as $user) {
            if(Qs::headSA($user->id) || $user->id === Auth::id()) {
                $skipped++;
                continue;
            }

            if($user->user_type == 'teacher' && $this->userTeachesSubject($user)) {
                $skipped++;
                continue;
            }

            $path = Qs::getUploadPath($user->user_type).$user->code;
            Storage::exists($path) ? Storage::deleteDirectory($path) : true;
            $this->user->delete($user->id);
            $deleted++;
        }

        if($deleted > 0) {
            session()->flash('flash_success', $deleted.' user account(s) deleted successfully.');
        }

        if($skipped > 0) {
            session()->flash('flash_warning', $skipped.' account(s) were skipped due to protection rules.');
        }

        return redirect()->route('users.index');
    }

    protected function userTeachesSubject($user)
    {
        $subjects = $this->my_class->findSubjectByTeacher($user->id);
        return ($subjects->count() > 0) ? true : false;
    }

}
