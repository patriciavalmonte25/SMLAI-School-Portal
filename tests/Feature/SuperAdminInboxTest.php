<?php

namespace Tests\Feature;

use App\User;
use App\Models\StudentEnrollment;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class AdministratorInboxTest extends TestCase
{

    public function test_registrar_inbox_page_lists_pending_approved_and_denied_forms()
    {
        $user = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $pendingEnrollment = StudentEnrollment::create([
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
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('http://localhost/registrar/students/inbox')
            ->assertStatus(200)
            ->assertSee('Inbox')
            ->assertSee('Pending Forms')
            ->assertSee('Approved Forms')
            ->assertSee('Denied Forms')
            ->assertSee('ENROLL20260816-001')
            ->assertSee(route('registrar.students.view_form', ['enrollment_id' => $pendingEnrollment->id]), false)
            ->assertDontSee('<th>#</th>', false);
    }

    public function test_registrar_inbox_orders_pending_applications_by_submission_time_desc()
    {
        $user = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        StudentEnrollment::create([
            'surname' => 'Alpha',
            'first_name' => 'A',
            'middle_name' => '',
            'dob' => '2014-01-01',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000001',
            'last_school_name' => 'School A',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address A',
            'grade_enrolling' => 'Grade 1',
            'account_username' => 'ENROLL20260816-001',
            'password' => 'ABCDEF',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        StudentEnrollment::create([
            'surname' => 'Bravo',
            'first_name' => 'B',
            'middle_name' => '',
            'dob' => '2014-02-02',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000002',
            'last_school_name' => 'School B',
            'last_school_type' => 'PUBLIC',
            'last_school_address' => 'Address B',
            'grade_enrolling' => 'Grade 2',
            'account_username' => 'ENROLL20260816-010',
            'password' => 'ZXHPH',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('http://localhost/registrar/students/inbox')
            ->assertStatus(200)
            ->assertSeeInOrder(['ENROLL20260816-010', 'ENROLL20260816-001']);
    }

    public function test_approved_and_denied_forms_are_sorted_by_their_decision_dates()
    {
        $registrar = User::create([
            'name' => 'Decision Date Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $makeEnrollment = function ($surname, $firstName, $status, $decisionDate, $submittedAt) use ($registrar) {
            $enrollment = StudentEnrollment::create([
                'surname' => $surname,
                'first_name' => $firstName,
                'dob' => '2014-01-01',
                'place_of_birth_town' => 'Town',
                'gender' => 'Female',
                'religion' => 'Christian',
                'home_town' => 'Town',
                'contact_number' => '09170000000',
                'last_school_name' => 'Sample School',
                'last_school_type' => 'PRIVATE',
                'last_school_address' => 'School Address',
                'grade_enrolling' => 'Grade 1',
                'account_username' => 'ENROLL' . uniqid(),
                'password' => 'Ab1!xyZ9',
                'status' => $status,
                'user_id' => $registrar->id,
                'approved_by' => $status === 'approved' ? $registrar->id : null,
                'approved_at' => $status === 'approved' ? $decisionDate : null,
                'denied_by' => $status === 'denied' ? $registrar->id : null,
                'denied_at' => $status === 'denied' ? $decisionDate : null,
                'denied_reason' => $status === 'denied' ? 'Incomplete documents.' : null,
            ]);

            $enrollment->created_at = $submittedAt;
            $enrollment->save();

            return $enrollment;
        };

        $makeEnrollment('Approved', 'Earlier Decision', 'approved', now()->subDays(2), now());
        $makeEnrollment('Approved', 'Later Decision', 'approved', now(), now()->subDays(2));
        $makeEnrollment('Denied', 'Earlier Decision', 'denied', now()->subDays(2), now());
        $makeEnrollment('Denied', 'Later Decision', 'denied', now(), now()->subDays(2));

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/inbox')
            ->assertOk()
            ->assertSeeInOrder([
                'Approved, Later Decision',
                'Approved, Earlier Decision',
                'Denied, Later Decision',
                'Denied, Earlier Decision',
            ]);
    }

    public function test_legacy_approved_and_denied_forms_fall_back_to_updated_at()
    {
        $registrar = User::create([
            'name' => 'Legacy Date Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $makeLegacyEnrollment = function ($surname, $firstName, $status, $submittedAt, $updatedAt) use ($registrar) {
            $enrollment = StudentEnrollment::create([
                'surname' => $surname,
                'first_name' => $firstName,
                'dob' => '2014-01-01',
                'place_of_birth_town' => 'Town',
                'gender' => 'Female',
                'religion' => 'Christian',
                'home_town' => 'Town',
                'contact_number' => '09170000000',
                'last_school_name' => 'Sample School',
                'last_school_type' => 'PRIVATE',
                'last_school_address' => 'School Address',
                'grade_enrolling' => 'Grade 1',
                'account_username' => 'ENROLL' . uniqid(),
                'password' => 'Ab1!xyZ9',
                'status' => $status,
                'user_id' => $registrar->id,
            ]);

            DB::table('student_enrollments')->where('id', $enrollment->id)->update([
                'created_at' => $submittedAt,
                'updated_at' => $updatedAt,
            ]);
        };

        $makeLegacyEnrollment('Approved', 'Older Update', 'approved', now(), now()->subDays(2));
        $makeLegacyEnrollment('Approved', 'Newer Update', 'approved', now()->subDays(2), now());
        $makeLegacyEnrollment('Denied', 'Older Update', 'denied', now(), now()->subDays(2));
        $makeLegacyEnrollment('Denied', 'Newer Update', 'denied', now()->subDays(2), now());

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/inbox')
            ->assertOk()
            ->assertSeeInOrder([
                'Approved, Newer Update',
                'Approved, Older Update',
                'Denied, Newer Update',
                'Denied, Older Update',
            ]);
    }

    public function test_registrar_approve_and_deny_move_submission_to_correct_tab()
    {
        $user = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $approved = StudentEnrollment::create([
            'surname' => 'Approved',
            'first_name' => 'Ann',
            'middle_name' => '',
            'dob' => '2014-03-03',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000003',
            'last_school_name' => 'School A',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address A',
            'grade_enrolling' => 'Grade 3',
            'account_username' => 'ENROLL20260816-030',
            'password' => 'ABCDE',
            'status' => 'pending',
            'user_id' => $user->id,
        ])->fresh();

        $denied = StudentEnrollment::create([
            'surname' => 'Denied',
            'first_name' => 'Dan',
            'middle_name' => '',
            'dob' => '2014-04-04',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000004',
            'last_school_name' => 'School B',
            'last_school_type' => 'PUBLIC',
            'last_school_address' => 'Address B',
            'grade_enrolling' => 'Grade 4',
            'account_username' => 'ENROLL20260816-040',
            'password' => 'FGHIJ',
            'status' => 'pending',
            'user_id' => $user->id,
        ])->fresh();

        $this->assertNotNull($approved->id);
        $this->assertNotNull($denied->id);
        $this->assertDatabaseHas('student_enrollments', ['id' => $approved->id]);

        $this->actingAs($user)
            ->get('http://localhost/registrar/students/forms/' . $approved->id)
            ->assertOk()
            ->assertSee('Enrollment Form: Approved, Ann')
            ->assertSee('Home Address')
            ->assertSee('Approve')
            ->assertSee('Deny')
            ->assertDontSee('Temporary Username')
            ->assertDontSee('Temporary Password')
            ->assertDontSee('name="surname"', false);

        $this->actingAs($user)
            ->from(route('registrar.students.inbox'))
            ->post('http://localhost/registrar/students/' . $approved->id . '/approve')
            ->assertRedirect(route('registrar.students.inbox'));

        $this->assertDatabaseHas('student_enrollments', ['id' => $approved->id, 'status' => 'approved']);

        $this->actingAs($user)
            ->from(route('registrar.students.inbox'))
            ->post('http://localhost/registrar/students/' . $denied->id . '/deny', ['reason' => 'Incomplete details.'])
            ->assertRedirect(route('registrar.students.inbox'));

        $this->assertDatabaseHas('student_enrollments', ['id' => $denied->id, 'status' => 'denied']);
    }

    public function test_registrar_can_bulk_delete_selected_forms()
    {
        $user = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $pending = StudentEnrollment::create([
            'surname' => 'Pending',
            'first_name' => 'Pat',
            'middle_name' => '',
            'dob' => '2014-05-05',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000005',
            'last_school_name' => 'School C',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address C',
            'grade_enrolling' => 'Grade 5',
            'account_username' => 'ENROLL20260816-050',
            'password' => 'KLMNO',
            'status' => 'pending',
            'user_id' => $user->id,
        ])->fresh();

        $approved = StudentEnrollment::create([
            'surname' => 'Approved',
            'first_name' => 'Ava',
            'middle_name' => '',
            'dob' => '2014-06-06',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000006',
            'last_school_name' => 'School D',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address D',
            'grade_enrolling' => 'Grade 6',
            'account_username' => 'ENROLL20260816-060',
            'password' => 'PQRST',
            'status' => 'approved',
            'user_id' => $user->id,
        ])->fresh();

        $kept = StudentEnrollment::create([
            'surname' => 'Kept',
            'first_name' => 'Kai',
            'middle_name' => '',
            'dob' => '2014-07-07',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000007',
            'last_school_name' => 'School E',
            'last_school_type' => 'PUBLIC',
            'last_school_address' => 'Address E',
            'grade_enrolling' => 'Grade 4',
            'account_username' => 'ENROLL20260816-070',
            'password' => 'UVWXY',
            'status' => 'denied',
            'user_id' => $user->id,
        ])->fresh();

        $this->actingAs($user)
            ->post('http://localhost/registrar/students/delete-selected', [
                'enrollment_ids' => [$pending->id, $approved->id],
            ])
            ->assertRedirect(route('registrar.students.inbox'));

        $this->assertDatabaseMissing('student_enrollments', ['id' => $pending->id]);
        $this->assertDatabaseMissing('student_enrollments', ['id' => $approved->id]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $kept->id]);
    }

    public function test_approved_forms_show_enroll_button_or_enrolled_state()
    {
        $user = User::create([
            'name' => 'Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $actionEnrollment = StudentEnrollment::create([
            'surname' => 'PendingEnrollAction',
            'first_name' => 'Anna',
            'middle_name' => '',
            'dob' => '2014-01-01',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000100',
            'last_school_name' => 'School X',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address X',
            'grade_enrolling' => 'Grade 1',
            'account_username' => 'ENROLL20260816-100',
            'password' => 'AAAAA',
            'status' => 'approved',
            'user_id' => $user->id,
        ]);

        $studentAccount = User::create([
            'name' => 'Anna Student',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => 'STUDENT' . uniqid(),
        ]);
        $secondStudentAccount = User::create([
            'name' => 'Anna Student Two',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => 'STUDENT' . uniqid(),
        ]);
        $parentAccount = User::create([
            'name' => 'Anna Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => 'PARENT' . uniqid(),
        ]);
        $actionEnrollment->accounts()->syncWithoutDetaching([
            $studentAccount->id => ['account_type' => 'Student'],
            $secondStudentAccount->id => ['account_type' => 'Student'],
            $parentAccount->id => ['account_type' => 'Parent'],
        ]);

        StudentEnrollment::create([
            'surname' => 'AlreadyEnrolled',
            'first_name' => 'Bella',
            'middle_name' => '',
            'dob' => '2014-01-01',
            'place_of_birth_town' => 'Town',
            'place_of_birth_province' => 'Province',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'home_province' => 'Province',
            'contact_number' => '09170000101',
            'last_school_name' => 'School Y',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Address Y',
            'grade_enrolling' => 'Grade 2',
            'account_username' => 'ENROLL20260816-101',
            'password' => 'BBBBB',
            'status' => 'approved',
            'user_id' => $user->id,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('http://localhost/registrar/students/inbox')
            ->assertStatus(200)
            ->assertSee('Create Student Account')
            ->assertSee('Create Parent Account')
            ->assertSee('2 Student accounts have already been made for this enrollment form; 3 total accounts.', false)
            ->assertSee('1 Parent account has already been made for this enrollment form; 3 total accounts.', false)
            ->assertSee('Mark Enrolled')
            ->assertSee('Are you sure?')
            ->assertSee('Cancel')
            ->assertSee('Proceed')
            ->assertSee('Enrolled');

        $this->actingAs($user)
            ->post('http://localhost/registrar/students/' . $actionEnrollment->id . '/mark-enrolled')
            ->assertRedirect(route('registrar.students.inbox'));

        $this->assertNotNull($actionEnrollment->fresh()->enrolled_at);

        $this->actingAs($user)
            ->post('http://localhost/registrar/students/' . $actionEnrollment->id . '/mark-enrolled')
            ->assertNotFound();

        $this->actingAs($user)
            ->post('http://localhost/registrar/students/' . $actionEnrollment->id . '/deny', ['reason' => 'No longer eligible.'])
            ->assertNotFound();

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $actionEnrollment->id,
            'status' => 'approved',
        ]);
    }
}
