<?php

namespace Tests\Feature;

use App\Models\AccountCreationNotice;
use App\Models\AccountCreationNoticeRecipient;
use App\Models\StudentEnrollment;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;

class PendingSubmissionPageTest extends TestCase
{
    public function test_temp_account_user_can_view_pending_submissions_with_tabs()
    {
        $tempUsername = 'ENROLL' . uniqid();

        $user = User::create([
            'name' => 'Applicant User',
            'username' => $tempUsername,
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
        ]);

        StudentEnrollment::create([
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
            'account_username' => $tempUsername,
            'password' => 'QWERT',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('http://localhost/pending-submissions')
            ->assertSeeText('Pending Submissions')
            ->assertSeeText('Outgoing Submissions')
            ->assertSeeText('Inbox')
            ->assertSeeText('Maria')
            ->assertSeeText($tempUsername);
    }

    public function test_temp_account_has_no_dashboard_and_cannot_change_password()
    {
        $user = User::create([
            'name' => 'Applicant User',
            'username' => 'ENROLL' . uniqid(),
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
        ]);

        $this->actingAs($user)
            ->get('http://localhost/dashboard')
            ->assertRedirect('http://localhost/pending-submissions');

        $originalPassword = $user->password;

        $this->actingAs($user)
            ->put('http://localhost/my_account/change_password', [
                'current_password' => 'secret123',
                'password' => 'NewPassword123',
                'password_confirmation' => 'NewPassword123',
            ])
            ->assertRedirect('http://localhost/pending-submissions');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_denied_temp_account_sees_notice_and_acknowledgment_deletes_account()
    {
        $tempUsername = 'ENROLL' . uniqid();

        $user = User::create([
            'name' => 'Applicant User',
            'username' => $tempUsername,
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
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
            'account_username' => $tempUsername,
            'password' => 'QWERT',
            'status' => 'denied',
            'denied_reason' => 'Incomplete birth certificate details.',
            'user_id' => $user->id,
        ])->fresh();

        $this->actingAs($user)
            ->get('http://localhost/pending-submissions')
            ->assertStatus(200)
            ->assertSeeText('Good day. This notice is to confirm that the enrollment application of')
            ->assertSeeText('Dela Cruz, Maria Santos')
            ->assertSeeText('REJECTED')
            ->assertSeeText('Reason: Incomplete birth certificate details.')
            ->assertSeeText('By checking this box, you acknowledge that you have read this notice. Your temporary account will be deleted IMMEDIATELY upon submission.');

        $this->assertNotNull($enrollment->id);

        $this->actingAs($user)
            ->post(route('pending_submissions.acknowledge', ['enrollment_id' => $enrollment->id]), ['acknowledged' => '1'])
            ->assertRedirect(route('public_home'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id, 'status' => 'denied']);
        $this->assertGuest();
    }

    public function test_approved_temp_account_sees_acceptance_notice_in_inbox()
    {
        $tempUsername = 'ENROLL' . uniqid();

        $user = User::create([
            'name' => 'Applicant User',
            'username' => $tempUsername,
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
        ]);

        StudentEnrollment::create([
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
            'account_username' => $tempUsername,
            'password' => 'QWERT',
            'status' => 'approved',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get('http://localhost/pending-submissions')
            ->assertStatus(200)
            ->assertSeeText('Approval Notice')
            ->assertSeeText('Good day. This notice is to confirm that the enrollment application of')
            ->assertSeeText('Dela Cruz, Maria Santos')
            ->assertSeeText('has been APPROVED.')
            ->assertSeeText('PSA Birth Certificate')
            ->assertSeeText('Baptismal Certificate')
            ->assertSeeText('Proof of Income')
            ->assertSeeText('SF9 Report Card')
            ->assertSeeText('SF10 Transcript of Record')
            ->assertSeeText('2x2 Picture')
            ->assertSeeText('Remember your current login details.');

        $this->assertSame(1, substr_count(
            $response->getContent(),
            'Good day. This notice is to confirm that the enrollment application of'
        ));
    }

    public function test_temp_account_sees_account_created_notice_and_can_acknowledge_three_day_deletion()
    {
        $tempUsername = 'ENROLL' . uniqid();

        $user = User::create([
            'name' => 'Applicant User',
            'username' => $tempUsername,
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
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
            'account_username' => $tempUsername,
            'password' => 'QWERT',
            'status' => 'approved',
            'user_id' => $user->id,
            'created_student_username' => 'GRADE8A001',
            'enrolled_at' => now(),
            'account_created_notice_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('http://localhost/pending-submissions')
            ->assertStatus(200)
            ->assertSeeText('Account Created Notice')
            ->assertSeeText('The student account of')
            ->assertSeeText('Dela Cruz, Maria Santos')
            ->assertSeeText('has been created.')
            ->assertSeeText('Username: GRADE8A001.')
            ->assertSeeText('Password: password.')
            ->assertSeeText('By checking this box, you acknowledge that you have read this notice. Your temporary account will be deleted THREE DAYS after submission.');

        $this->actingAs($user)
            ->post(route('pending_submissions.account_created_acknowledge', ['enrollment_id' => $enrollment->id]), ['acknowledged' => '1'])
            ->assertRedirect(route('pending_submissions'));

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $enrollment->id,
        ]);

        $updated = StudentEnrollment::find($enrollment->id);
        $this->assertNotNull($updated->account_created_acknowledged_at);
        $this->assertNotNull($updated->temporary_account_delete_at);
    }

    public function test_temp_account_is_deleted_after_three_day_notice_period()
    {
        $tempUsername = 'ENROLL' . uniqid();

        $user = User::create([
            'name' => 'Applicant User',
            'username' => $tempUsername,
            'email' => 'applicant-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'enroll',
            'code' => 'ENROLL' . uniqid(),
        ]);

        StudentEnrollment::create([
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
            'account_username' => $tempUsername,
            'password' => 'QWERT',
            'status' => 'approved',
            'user_id' => $user->id,
            'created_student_username' => 'GRADE8A001',
            'enrolled_at' => now(),
            'account_created_notice_at' => now()->subDays(5),
            'account_created_acknowledged_at' => now()->subDays(4),
            'temporary_account_delete_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('http://localhost/pending-submissions')
            ->assertRedirect('http://localhost');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_parent_acknowledgment_expires_only_the_account_creation_notice()
    {
        $parent = User::create([
            'name' => 'Parent Account',
            'username' => 'PARENT' . uniqid(),
            'email' => 'parent-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'parent',
            'code' => 'PARENT' . uniqid(),
        ]);
        $enrollment = StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => $parent->username,
            'status' => 'approved',
            'user_id' => $parent->id,
        ]);
        $notice = AccountCreationNotice::create([
            'student_enrollment_id' => $enrollment->id,
            'accounts' => [['account_type' => 'Student', 'username' => 'STUDENT123', 'password' => 'password']],
        ]);
        AccountCreationNoticeRecipient::create([
            'account_creation_notice_id' => $notice->id,
            'user_id' => $parent->id,
            'account_type' => 'Parent',
            'username' => $parent->username,
        ]);

        $response = $this->actingAs($parent)->get('http://localhost/pending-submissions');
        $this->assertSame(200, $response->status(), substr(strip_tags($response->getContent()), 0, 1000));
        $response->assertSeeText('This notice will be deleted THREE DAYS after acknowledgment.');

        $this->actingAs($parent)
            ->post('http://localhost/pending-submissions/account-notice-acknowledge/' . $notice->id, ['acknowledged' => '1'])
            ->assertRedirect(route('pending_submissions'))
            ->assertSessionHas('flash_success', 'Acknowledgment recorded. This notice will be deleted after 3 days.');

        $notice->refresh();
        $this->assertNotNull($notice->acknowledged_at);
        $this->assertNotNull($notice->temporary_account_delete_at);
        $this->assertNull($enrollment->fresh()->temporary_account_delete_at);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'user_type' => 'parent']);

        $this->actingAs($parent)
            ->get('http://localhost/pending-submissions')
            ->assertOk()
            ->assertSeeText('This notice will be deleted on');

        Carbon::setTestNow($notice->temporary_account_delete_at->copy()->addSecond());
        try {
            $this->actingAs($parent)
                ->get('http://localhost/pending-submissions')
                ->assertOk()
                ->assertDontSee('STUDENT123');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseMissing('account_creation_notices', ['id' => $notice->id]);
        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'user_type' => 'parent']);
        $this->actingAs($parent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertDontSee('STUDENT123');
    }

    public function test_parent_account_created_notice_expires_without_deleting_parent_or_enrollment()
    {
        $parent = User::create([
            'name' => 'Parent Applicant',
            'username' => 'PARENT' . uniqid(),
            'email' => 'parent-' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'user_type' => 'parent',
            'code' => 'PARENT' . uniqid(),
        ]);
        $enrollment = StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Child',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'contact_number' => '09175551234',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Poblacion, Sta. Maria',
            'grade_enrolling' => 'Grade 1',
            'account_username' => $parent->username,
            'status' => 'approved',
            'user_id' => $parent->id,
            'enrolled_at' => now(),
            'account_created_notice_at' => now(),
            'created_student_username' => 'STUDENT456',
        ]);

        $this->actingAs($parent)
            ->get('http://localhost/pending-submissions')
            ->assertOk()
            ->assertSeeText('This notice will be deleted THREE DAYS after acknowledgment.');

        $this->actingAs($parent)
            ->post('http://localhost/pending-submissions/account-created-acknowledge/' . $enrollment->id, ['acknowledged' => '1'])
            ->assertRedirect(route('pending_submissions'))
            ->assertSessionHas('flash_success', 'Acknowledgment recorded. This notice will be deleted after 3 days.');

        $enrollment->refresh();
        $this->assertNotNull($enrollment->account_created_acknowledged_at);
        $this->assertNotNull($enrollment->temporary_account_delete_at);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'user_type' => 'parent']);

        Carbon::setTestNow($enrollment->temporary_account_delete_at->copy()->addSecond());
        try {
            $this->actingAs($parent)
                ->get('http://localhost/pending-submissions')
                ->assertOk()
                ->assertDontSee('STUDENT456');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseHas('student_enrollments', ['id' => $enrollment->id]);
        $this->assertNull($enrollment->fresh()->account_created_notice_at);
        $this->assertDatabaseHas('users', ['id' => $parent->id, 'user_type' => 'parent']);
    }
}
