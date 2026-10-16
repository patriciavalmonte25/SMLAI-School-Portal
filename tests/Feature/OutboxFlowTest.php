<?php

namespace Tests\Feature;

use App\Models\AccountCreationNotice;
use App\Models\AccountCreationNoticeRecipient;
use App\Models\StudentEnrollment;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OutboxFlowTest extends TestCase
{
    private function makeRegistrar()
    {
        return User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);
    }

    private function makeAccountUser($userType, $username)
    {
        $user = User::create([
            'name' => ucfirst($userType) . ' Account',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $userType,
            'code' => strtoupper($userType) . uniqid(),
        ]);

        DB::table('users')->where('id', $user->id)->update(['password_encrypted' => encrypt('password')]);

        return $user;
    }

    private function makeTemporaryEnrollment()
    {
        $temporaryUser = User::create([
            'name' => 'Juan Dela Cruz',
            'username' => 'ENROLL' . uniqid(),
            'password' => bcrypt('temp-password'),
            'user_type' => 'enroll',
            'code' => 'ENR' . uniqid(),
        ]);

        return StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Juan',
            'dob' => '2013-02-14',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => $temporaryUser->username,
            'password' => 'TEMPPW',
            'status' => 'approved',
            'user_id' => $temporaryUser->id,
        ]);
    }

    public function test_registrar_can_send_account_creation_notice_from_outbox()
    {
        $registrar = $this->makeRegistrar();
        $enrollment = $this->makeTemporaryEnrollment();
        $studentUsername = 'STUDENT' . uniqid();
        $parentUsername = 'PARENT' . uniqid();
        $student = $this->makeAccountUser('student', $studentUsername);
        $parent = $this->makeAccountUser('parent', $parentUsername);
        $enrollment->accounts()->syncWithoutDetaching([
            $student->id => ['account_type' => 'Student'],
            $parent->id => ['account_type' => 'Parent'],
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/outbox')
            ->assertStatus(200)
            ->assertSee('Enrollment Submitter')
            ->assertSee('class="form-control select-search"', false)
            ->assertSee("window.jQuery(enrollmentSelect).on('change', renderLinkedAccounts);", false)
            ->assertSee('Temporary account:', false)
            ->assertSee('Dela Cruz, Juan')
            ->assertSee("['account_type', account.account_type]", false)
            ->assertSee("['username', account.username]", false)
            ->assertSee($studentUsername, false)
            ->assertSee($parentUsername, false);

        $beforeCount = AccountCreationNotice::count();

        $this->actingAs($registrar)
            ->post('http://localhost/registrar/students/outbox', [
                'student_enrollment_id' => $enrollment->id,
                'accounts' => [
                    ['account_type' => 'Student', 'username' => $studentUsername, 'password' => 'password'],
                    ['account_type' => 'Parent', 'username' => $parentUsername, 'password' => 'password'],
                ],
            ])
            ->assertRedirect(route('registrar.students.outbox'))
            ->assertSessionHas('flash_success', 'Account creation notice sent to the enrollee.');

        $this->assertSame($beforeCount + 1, AccountCreationNotice::count());
        $notice = AccountCreationNotice::where('student_enrollment_id', $enrollment->id)->latest('id')->first();
        $this->assertSame($enrollment->id, $notice->student_enrollment_id);
        $this->assertCount(2, $notice->accounts);
        $this->assertSame('Student', $notice->accounts[0]['account_type']);
        $this->assertSame($studentUsername, $notice->accounts[0]['username']);
        $this->assertSame('password', $notice->accounts[0]['password']);
        $this->assertCount(0, $notice->recipients);

        $this->actingAs($enrollment->user)
            ->get('http://localhost/pending-submissions')
            ->assertStatus(200)
            ->assertSee('The following accounts have been created.', false)
            ->assertSee($studentUsername)
            ->assertSee($parentUsername);

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/outbox')
            ->assertStatus(200)
            ->assertSee('Delete Selected')
            ->assertSee('Select All')
            ->assertSee('Deselect All')
            ->assertSee('outbox-notice-checkbox', false);

        $this->actingAs($student)
            ->get('http://localhost/inbox')
            ->assertStatus(200)
            ->assertDontSee('Good day. The account details for', false)
            ->assertDontSee($parentUsername);

        $this->actingAs($parent)
            ->get('http://localhost/inbox')
            ->assertStatus(200)
            ->assertDontSee('Good day. The account details for', false)
            ->assertDontSee($studentUsername);
    }

    public function test_parent_originated_enrollment_delivers_notice_to_parent_account()
    {
        $registrar = $this->makeRegistrar();
        $parent = $this->makeAccountUser('parent', 'PARENT' . uniqid());
        $student = $this->makeAccountUser('student', 'STUDENT' . uniqid());
        $enrollment = StudentEnrollment::create([
            'surname' => 'Parent',
            'first_name' => 'Applicant',
            'dob' => '2013-02-14',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => $parent->username,
            'password' => null,
            'status' => 'approved',
            'user_id' => $parent->id,
        ]);
        $enrollment->accounts()->sync([
            $parent->id => ['account_type' => 'Parent'],
            $student->id => ['account_type' => 'Student'],
        ]);

        $outboxPage = $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/outbox')
            ->assertOk();
        $outboxHtml = $outboxPage->getContent();
        $this->assertMatchesRegularExpression('/"username":"' . preg_quote($student->username, '/') . '"/', $outboxHtml);
        $this->assertDoesNotMatchRegularExpression('/"username":"' . preg_quote($parent->username, '/') . '"/', $outboxHtml);

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/outbox')
            ->assertOk()
            ->assertSee('Parent account:', false)
            ->assertSee($parent->username, false)
            ->assertSee('Applicant: Parent, Applicant', false);

        $this->actingAs($registrar)
            ->post('http://localhost/registrar/students/outbox', [
                'student_enrollment_id' => $enrollment->id,
                'accounts' => [
                    ['account_type' => 'Student', 'username' => $student->username, 'password' => 'password'],
                ],
            ])
            ->assertRedirect(route('registrar.students.outbox'));

        $notice = AccountCreationNotice::where('student_enrollment_id', $enrollment->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('account_creation_notice_recipients', [
            'account_creation_notice_id' => $notice->id,
            'user_id' => $parent->id,
            'account_type' => 'Student',
            'username' => $student->username,
        ]);

        $this->actingAs($parent)
            ->get('http://localhost/inbox')
            ->assertStatus(200)
            ->assertSee('Good day. The account details for Parent, Applicant are as follows:', false)
            ->assertSee($student->username, false)
            ->assertSee('By checking this, you acknowledge that this account has been created successfully, and the pending submission and this notice will be removed.', false)
            ->assertSee('class="text-right"', false)
            ->assertSee('>Submit</button>', false)
            ->assertDontSee('Submit Acknowledgment');
        $this->actingAs($parent)
            ->get('http://localhost/parent-enrollment?tab=inbox')
            ->assertOk()
            ->assertSee('By checking this, you acknowledge that this account has been created successfully, and the pending submission and this notice will be removed.', false)
            ->assertSee('class="text-right"', false)
            ->assertSee('>Submit</button>', false)
            ->assertDontSee('Submit Acknowledgment');

        $recipient = AccountCreationNoticeRecipient::where('account_creation_notice_id', $notice->id)
            ->where('user_id', $parent->id)
            ->firstOrFail();
        $this->actingAs($parent)
            ->from('http://localhost/parent-enrollment?tab=inbox')
            ->post('http://localhost/inbox/account-creation-notices/' . $recipient->id . '/acknowledge')
            ->assertRedirect('http://localhost/parent-enrollment?tab=inbox')
            ->assertSessionHas('flash_success', 'Acknowledgment recorded. The pending submission and notice were removed.');

        $this->assertDatabaseMissing('account_creation_notice_recipients', ['id' => $recipient->id]);
        $this->assertDatabaseHas('account_creation_notices', ['id' => $notice->id]);
        $this->actingAs($parent)
            ->from('http://localhost/parent-enrollment?tab=inbox')
            ->post('http://localhost/inbox/account-creation-notices/' . $recipient->id . '/acknowledge')
            ->assertRedirect('http://localhost/parent-enrollment?tab=inbox')
            ->assertSessionHas('flash_success', 'This notice has already been acknowledged or removed.');
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
            'account_created_acknowledged_at' => now()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'user_type' => 'parent']);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'user_type' => 'student']);

        $this->actingAs($parent)
            ->get('http://localhost/parent-enrollment')
            ->assertOk()
            ->assertDontSee('Parent, Applicant');
        $this->actingAs($parent)
            ->get('http://localhost/pending-submissions')
            ->assertOk()
            ->assertDontSee('Parent, Applicant');
        $this->actingAs($parent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertDontSee($student->username);
    }

    public function test_registrar_can_delete_selected_sent_notices()
    {
        $registrar = $this->makeRegistrar();
        $enrollment = $this->makeTemporaryEnrollment();
        $student = $this->makeAccountUser('student', 'STUDENT' . uniqid());
        $notice = AccountCreationNotice::create([
            'student_enrollment_id' => $enrollment->id,
            'accounts' => [
                ['account_type' => 'Student', 'username' => $student->username],
            ],
            'sent_by' => $registrar->id,
        ]);
        $notice->recipients()->create([
            'user_id' => $student->id,
            'account_type' => 'Student',
            'username' => $student->username,
        ]);

        $this->actingAs($registrar)
            ->post('http://localhost/registrar/students/outbox/delete-selected', [
                'notice_ids' => [$notice->id],
            ])
            ->assertRedirect(route('registrar.students.outbox'));

        $this->assertNull(AccountCreationNotice::find($notice->id));
        $this->assertSame(0, AccountCreationNoticeRecipient::where('account_creation_notice_id', $notice->id)->count());
    }

    public function test_temporary_user_sees_notice_and_acknowledging_schedules_deletion()
    {
        $registrar = $this->makeRegistrar();
        $enrollment = $this->makeTemporaryEnrollment();

        $notice = AccountCreationNotice::create([
            'student_enrollment_id' => $enrollment->id,
            'accounts' => [
                ['account_type' => 'Student', 'username' => 'STUDENT001', 'password' => 'password'],
            ],
            'sent_by' => $registrar->id,
        ]);

        $enrollment->account_created_notice_at = now();
        $enrollment->created_student_username = 'STUDENT001';
        $enrollment->save();

        $temporaryUser = $enrollment->user;

        $this->actingAs($temporaryUser)
            ->get('http://localhost/pending-submissions')
            ->assertStatus(200)
            ->assertSee('enrollment application of Dela Cruz, Juan has been APPROVED', false)
            ->assertSee('PSA Birth Certificate')
            ->assertSee('SF10 Transcript of Record')
            ->assertSee('student and parent accounts for the school portal will be created')
            ->assertSee('Temporary Username')
            ->assertSee('The following accounts have been created.', false)
            ->assertSee('Account Type: Student. Username: STUDENT001. Password: "password".', false);

        $this->actingAs($temporaryUser)
            ->post('http://localhost/pending-submissions/account-notice-acknowledge/' . $notice->id, [
                'acknowledged' => 1,
            ])
            ->assertRedirect(route('pending_submissions'));

        $notice->refresh();
        $this->assertNotNull($notice->acknowledged_at);
        $this->assertNotNull($notice->temporary_account_delete_at);

        Carbon::setTestNow($notice->temporary_account_delete_at->addMinute());

        $this->actingAs($temporaryUser)
            ->get('http://localhost/pending-submissions')
            ->assertRedirect(route('public_home'));

        $this->assertNull(User::find($temporaryUser->id));

        Carbon::setTestNow();
    }
}
