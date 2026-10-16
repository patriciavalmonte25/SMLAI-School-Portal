<?php

namespace App\Http\Controllers\SuperAdmin;

use App\User;
use App\Helpers\Qs;
use App\Helpers\AccountCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EnrollStudentRequest;
use App\Models\StudentEnrollment;
use App\Repositories\MyClassRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentEnrollmentController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->my_class = $my_class;
    }

    public function create(Request $request)
    {
        $data['my_classes'] = $this->my_class->all();
        $data['genders'] = ['Male', 'Female'];
        $data['last_school_types'] = ['PRIVATE', 'PUBLIC'];
        $data['student_grades'] = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        $sourceEnrollmentId = $request->query('enrollment_id');
        $data['source_enrollment'] = null;
        if ($sourceEnrollmentId) {
            $data['source_enrollment'] = StudentEnrollment::where('id', $sourceEnrollmentId)
                ->where('status', 'approved')
                ->firstOrFail();
        }

        return view('pages.super_admin.students.enroll', $data);
    }

    public function store(EnrollStudentRequest $req)
    {
        $data = $req->validated();

        DB::transaction(function () use ($data) {
            $password = AccountCredentials::randomPassword(8);
            $passwordHash = Hash::make($password);

            $studentUser = User::create([
                'name' => Qs::formatNameFromParts($data['surname'], $data['first_name'], $data['middle_name'] ?? null),
                'username' => $data['username'],
                'code' => strtoupper(Str::random(10)),
                'user_type' => 'student',
                'dob' => $data['dob'],
                'gender' => $data['gender'],
                'password' => $passwordHash,
            ]);
            DB::table('users')->where('id', $studentUser->id)->update([
                'password_encrypted' => encrypt($password),
            ]);

            $enrollmentData = $data;
            $sourceEnrollmentId = $enrollmentData['source_enrollment_id'] ?? null;
            unset($enrollmentData['source_enrollment_id']);

            if (empty($enrollmentData['place_of_birth_province'])) {
                $enrollmentData['place_of_birth_province'] = $enrollmentData['place_of_birth_town'];
            }
            if (empty($enrollmentData['home_province'])) {
                $enrollmentData['home_province'] = $enrollmentData['home_town'];
            }
            $enrollmentData['password'] = $passwordHash;
            $enrollmentData['account_username'] = $data['username'];
            unset($enrollmentData['username']);

            if ($sourceEnrollmentId) {
                $sourceEnrollment = StudentEnrollment::where('id', $sourceEnrollmentId)
                    ->where('status', 'approved')
                    ->firstOrFail();

                $sourceEnrollment->fill([
                    'surname' => $data['surname'],
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'dob' => $data['dob'],
                    'place_of_birth_town' => $enrollmentData['place_of_birth_town'],
                    'place_of_birth_province' => $enrollmentData['place_of_birth_province'],
                    'gender' => $data['gender'],
                    'religion' => $data['religion'],
                    'home_town' => $enrollmentData['home_town'],
                    'home_province' => $enrollmentData['home_province'],
                    'father_name' => $data['father_name'] ?? null,
                    'father_occupation' => $data['father_occupation'] ?? null,
                    'mother_name' => $data['mother_name'] ?? null,
                    'mother_occupation' => $data['mother_occupation'] ?? null,
                    'guardian_name' => $data['guardian_name'] ?? null,
                    'guardian_occupation' => $data['guardian_occupation'] ?? null,
                    'guardian_relation' => $data['guardian_relation'] ?? null,
                    'contact_number' => $data['contact_number'],
                    'last_school_name' => $data['last_school_name'] ?? null,
                    'last_school_type' => $data['last_school_type'] ?? null,
                    'last_school_address' => $data['last_school_address'] ?? null,
                    'grade_enrolling' => $data['grade_enrolling'],
                    'my_class_id' => $data['my_class_id'],
                    'section_id' => $data['section_id'] ?? null,
                    'pathway' => $data['pathway'] ?? null,
                    'student_id' => $data['student_id'] ?? null,
                    'enrolled_student_user_id' => $studentUser->id,
                    'created_student_username' => $data['username'],
                ]);
                $sourceEnrollment->save();
                $sourceEnrollment->accounts()->syncWithoutDetaching([
                    $studentUser->id => ['account_type' => 'Student'],
                ]);
                $parentIds = $sourceEnrollment->accounts()
                    ->wherePivot('account_type', 'Parent')
                    ->pluck('users.id');
                $studentUser->parents()->syncWithoutDetaching($parentIds);
            } else {
                StudentEnrollment::create($enrollmentData);
            }
        });

        if ($req->expectsJson()) {
            return response()->json([
                'ok' => true,
                'msg' => 'Student account created successfully.',
            ]);
        }

        return redirect()->route('registrar.students.enroll')->with('flash_success', 'Student account created successfully.');
    }
}
