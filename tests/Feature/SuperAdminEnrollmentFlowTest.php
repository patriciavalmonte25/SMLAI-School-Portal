<?php

namespace Tests\Feature;

use App\Models\MyClass;
use App\Models\StudentEnrollment;
use App\Helpers\Qs;
use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorEnrollmentFlowTest extends TestCase
{
    public function test_enroll_page_prefills_from_approved_submission()
    {
        $administrator = User::create([
            'name' => 'Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $enrollment = StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'place_of_birth_province' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'home_province' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => 'ENROLL20260816-001',
            'password' => 'ZXHPH',
            'status' => 'approved',
            'user_id' => $administrator->id,
        ]);

        $this->actingAs($administrator)
            ->get('http://localhost/registrar/students/enroll?enrollment_id=' . $enrollment->id)
            ->assertStatus(200)
            ->assertSee('data-success-popup="true"', false)
            ->assertSee('data-confirm-label="Create Account"', false)
            ->assertSee('value="Dela Cruz"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('value="Santos"', false)
            ->assertSee('name="dob"', false)
            ->assertSee('type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy"', false)
            ->assertSee('value="Sample School"', false);
    }

    public function test_creating_student_from_approved_submission_does_not_mark_enrolled_or_send_notice()
    {
        $administrator = User::create([
            'name' => 'Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $class = MyClass::create([
            'name' => 'Section A-' . uniqid(),
            'class_type_id' => null,
        ]);

        $enrollment = StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'place_of_birth_province' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'home_province' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => 'ENROLL20260816-001',
            'password' => 'ZXHPH',
            'status' => 'approved',
            'user_id' => $administrator->id,
        ]);
        $parent = User::create([
            'name' => 'Applicant Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => 'PARENT' . uniqid(),
        ]);
        $enrollment->accounts()->syncWithoutDetaching([
            $parent->id => ['account_type' => 'Parent'],
        ]);

        $beforeCount = StudentEnrollment::count();

        $this->actingAs($administrator)
            ->postJson('http://localhost/registrar/students/enroll', [
                'source_enrollment_id' => $enrollment->id,
                'surname' => 'Dela Cruz',
                'first_name' => 'Maria',
                'dob' => '2014-05-10',
                'place_of_birth_town' => 'Sta. Maria',
                'gender' => 'Female',
                'religion' => 'Christian',
                'home_town' => 'Sta. Maria',
                'contact_number' => '09175551234',
                'grade_enrolling' => 'Grade 1',
                'student_grade' => 'Grade 1',
                'my_class_id' => $class->id,
                'section_id' => 'not-a-field-on-this-form',
                'username' => 'GRADE1A-' . uniqid(),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('msg', 'Student account created successfully.');

        $this->assertSame($beforeCount, StudentEnrollment::count());

        $updated = StudentEnrollment::find($enrollment->id);
        $this->assertNull($updated->middle_name);
        $this->assertNull($updated->father_name);
        $this->assertNull($updated->father_occupation);
        $this->assertNull($updated->mother_name);
        $this->assertNull($updated->mother_occupation);
        $this->assertNull($updated->guardian_name);
        $this->assertNull($updated->guardian_occupation);
        $this->assertNull($updated->guardian_relation);
        $this->assertNull($updated->last_school_type);
        $this->assertNull($updated->last_school_address);
        $this->assertNull($updated->enrolled_at);
        $this->assertNotNull($updated->enrolled_student_user_id);
        $this->assertNull($updated->account_created_notice_at);
        $this->assertNotNull($updated->created_student_username);
        $this->assertNull($updated->account_created_acknowledged_at);
        $this->assertNull($updated->temporary_account_delete_at);

        $studentUser = User::findOrFail($updated->enrolled_student_user_id);
        $password = decrypt($studentUser->password_encrypted);
        $this->assertSame(8, strlen($password));
        $this->assertMatchesRegularExpression('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8}$/', $password);
        $this->assertTrue(Hash::check($password, $studentUser->password));
        $this->assertSame('Student', $updated->accounts()->where('users.id', $studentUser->id)->first()->pivot->account_type);
        $this->assertTrue($parent->children()->where('users.id', $studentUser->id)->exists());
        $this->assertTrue($studentUser->parents()->where('users.id', $parent->id)->exists());
        $this->actingAs($administrator)
            ->get(route('users.show', Qs::hash($parent->id)))
            ->assertOk()
            ->assertSee('<ul class="mb-0 pl-3">', false)
            ->assertSee($studentUser->name);
        $this->actingAs($administrator)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('<ul class="mb-0 pl-3">', false)
            ->assertSee('<li>' . $studentUser->name, false);
        $this->actingAs($parent)
            ->get(route('my_children'))
            ->assertOk()
            ->assertSee($studentUser->name);
    }

    public function test_student_cannot_be_created_from_personal_information_step_alone()
    {
        $administrator = User::create([
            'name' => 'Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $beforeCount = User::where('user_type', 'student')->count();

        $this->actingAs($administrator)
            ->post('http://localhost/registrar/students/enroll', [
                'surname' => 'Dela Cruz',
                'first_name' => 'Maria',
                'username' => 'GRADE1A-' . uniqid(),
            ])
            ->assertSessionHasErrors([
                'dob',
                'place_of_birth_town',
                'gender',
                'religion',
                'home_town',
                'contact_number',
                'grade_enrolling',
                'student_grade',
                'my_class_id',
            ]);

        $this->assertSame($beforeCount, User::where('user_type', 'student')->count());
    }
}
