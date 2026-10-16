<?php

namespace Tests\Feature;

use App\User;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollmentSubmissionTest extends TestCase
{
    use WithoutMiddleware;

    public function test_public_enrollment_form_shows_date_of_birth_as_month_day_year()
    {
        \View::share('errors', new \Illuminate\Support\ViewErrorBag());

        $this->get('http://localhost/enrollment-form')
            ->assertOk()
            ->assertSee('Date of Birth (MM/DD/YYYY)', false)
            ->assertSee('placeholder="MM/DD/YYYY"', false)
            ->assertDontSee('type="date" name="dob"', false);
    }

    public function test_public_enrollment_creates_temporary_account_and_record()
    {
        $response = $this->post('http://localhost/enrollment-form', [
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'dob' => '05/10/2014',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'grade_enrolling' => 'Grade 1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['user_type' => 'enroll']);
        $this->assertDatabaseHas('student_enrollments', ['status' => 'pending']);
        $this->assertTrue(User::where('user_type', 'enroll')->exists());

        $enrollment = StudentEnrollment::latest('id')->firstOrFail();
        $this->assertNull($enrollment->middle_name);
        $this->assertNull($enrollment->father_name);
        $this->assertNull($enrollment->father_occupation);
        $this->assertNull($enrollment->mother_name);
        $this->assertNull($enrollment->mother_occupation);
        $this->assertNull($enrollment->guardian_name);
        $this->assertNull($enrollment->guardian_occupation);
        $this->assertNull($enrollment->guardian_relation);
        $this->assertNull($enrollment->last_school_type);
        $this->assertNull($enrollment->last_school_address);
        $temporaryUser = $enrollment->user;
        $this->assertSame('05/10/2014', $enrollment->dob);
        $this->assertSame(8, strlen($enrollment->password));
        $this->assertMatchesRegularExpression('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8}$/', $enrollment->password);
        $this->assertTrue(Hash::check($enrollment->password, $temporaryUser->password));
    }

    public function test_existing_student_can_submit_without_creating_a_temporary_account()
    {
        $student = User::create([
            'name' => 'Existing Student',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => 'STUDENT' . uniqid(),
        ]);

        $beforeEnrollUsers = User::where('user_type', 'enroll')->count();
        $response = $this->actingAs($student)->post('http://localhost/enrollment-form', [
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'grade_enrolling' => 'Grade 1',
        ]);

        $response->assertRedirect(route('pending_submissions'));
        $this->assertSame($beforeEnrollUsers, User::where('user_type', 'enroll')->count());
        $enrollment = StudentEnrollment::latest('id')->firstOrFail();
        $this->assertSame($student->id, $enrollment->user_id);
        $this->assertSame($student->username, $enrollment->account_username);
        $this->assertSame('Student', $enrollment->accounts()->first()->pivot->account_type);
    }

    public function test_parent_enrollment_page_has_three_tabs_and_submission_stays_on_parent_account()
    {
        $parent = User::create([
            'name' => 'Existing Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => 'PARENT' . uniqid(),
        ]);

        \View::share('errors', new \Illuminate\Support\ViewErrorBag());
        $page = $this->actingAs($parent)->get('http://localhost/parent-enrollment');
        $page->assertOk()
            ->assertSee('New Enrollment')
            ->assertSee('Pending Submissions')
            ->assertSee('Inbox')
            ->assertSee('placeholder="MM/DD/YYYY"', false)
            ->assertSee('data-date-format="mm/dd/yyyy"', false);

        $beforeEnrollUsers = User::where('user_type', 'enroll')->count();
        $response = $this->actingAs($parent)->post(route('enrollment_form.store'), [
            'surname' => 'Parent Applicant',
            'first_name' => 'Child',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'grade_enrolling' => 'Grade 1',
        ]);

        $response->assertRedirect(route('parent_enrollment', ['tab' => 'pending']));
        $this->assertSame($beforeEnrollUsers, User::where('user_type', 'enroll')->count());

        $enrollment = StudentEnrollment::latest('id')->firstOrFail();
        $this->assertNull($enrollment->middle_name);
        $this->assertNull($enrollment->father_name);
        $this->assertNull($enrollment->mother_name);
        $this->assertNull($enrollment->guardian_name);
        $this->assertNull($enrollment->last_school_type);
        $this->assertNull($enrollment->last_school_address);
        $this->assertSame($parent->id, $enrollment->user_id);
        $this->assertSame($parent->id, $enrollment->accounts()->first()->id);
        $this->assertSame('Parent', $enrollment->accounts()->first()->pivot->account_type);
    }
}
