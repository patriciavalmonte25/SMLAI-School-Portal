<?php

namespace Tests\Feature;

use App\Models\BillingList;
use App\Models\BillingNotice;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\User;
use Tests\TestCase;

class AccountantNoticesTest extends TestCase
{
    private function makeUser($type, $name)
    {
        return User::create([
            'name' => $name,
            'username' => $type . '-' . uniqid(),
            'email' => $type . '-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($type) . uniqid(),
        ]);
    }

    public function test_accountant_can_send_notice_to_linked_parents_and_mark_it_paid()
    {
        $accountant = $this->makeUser('accountant', 'School Accountant');
        $student = $this->makeUser('student', 'Jamie Student');
        $firstParent = $this->makeUser('parent', 'Parent One');
        $secondParent = $this->makeUser('parent', 'Parent Two');
        $sectionOrderMarker = uniqid();
        $kinderClass = MyClass::create(['name' => 'Kinder ' . $sectionOrderMarker, 'class_type_id' => null]);
        $kinderSection = Section::create(['name' => 'Kinder Section ' . $sectionOrderMarker, 'my_class_id' => $kinderClass->id, 'active' => 1]);
        $gradeTwoClass = MyClass::create(['name' => 'Grade 2 ' . $sectionOrderMarker, 'class_type_id' => null]);
        $gradeTwoSection = Section::create(['name' => 'Grade 2 Section ' . $sectionOrderMarker, 'my_class_id' => $gradeTwoClass->id, 'active' => 1]);
        $gradeTenClassName = 'Grade 10 ' . uniqid();
        $gradeTenClass = MyClass::create(['name' => $gradeTenClassName, 'class_type_id' => null]);
        $stPeterSection = Section::create(['name' => 'St. Peter', 'my_class_id' => $gradeTenClass->id, 'active' => 1]);
        $gradeTwelveClassName = 'Grade 12 ' . uniqid();
        $gradeTwelveClass = MyClass::create(['name' => $gradeTwelveClassName, 'class_type_id' => null]);
        $className = 'Grade 3 ' . uniqid();
        $class = MyClass::create(['name' => $className, 'class_type_id' => null]);
        $section = Section::create(['name' => 'Blue', 'my_class_id' => $class->id, 'active' => 1]);
        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'my_parent_id' => $firstParent->id,
            'session' => '2026',
            'grad' => 0,
        ]);
        $alphabeticallyFirstStudent = $this->makeUser('student', 'Alpha Student');
        StudentRecord::create([
            'user_id' => $alphabeticallyFirstStudent->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'session' => '2026',
            'grad' => 0,
        ]);

        $temporaryUser = $this->makeUser('enroll', 'Temporary User');
        $enrollment = StudentEnrollment::create([
            'surname' => 'Student',
            'first_name' => 'Jamie',
            'dob' => '2016-01-01',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000000',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'School Address',
            'grade_enrolling' => 'Grade 3',
            'account_username' => $temporaryUser->username,
            'password' => 'TEMP123',
            'status' => 'approved',
            'user_id' => $temporaryUser->id,
            'enrolled_student_user_id' => $student->id,
        ]);
        $enrollment->accounts()->syncWithoutDetaching([
            $student->id => ['account_type' => 'Student'],
            $secondParent->id => ['account_type' => 'Parent'],
        ]);

        $enrollmentOnlyStudent = $this->makeUser('student', 'Beta Student');
        $enrollmentOnlyParent = $this->makeUser('parent', 'Parent Three');
        $enrollmentOnlyTempUser = $this->makeUser('enroll', 'Second Temporary User');
        $enrollmentOnly = StudentEnrollment::create([
            'surname' => 'Student',
            'first_name' => 'Beta',
            'dob' => '2016-02-01',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000001',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'School Address',
            'grade_enrolling' => 'Grade 3',
            'my_class_id' => $gradeTwelveClass->id,
            'section_id' => null,
            'account_username' => $enrollmentOnlyTempUser->username,
            'password' => 'TEMP124',
            'status' => 'approved',
            'user_id' => $enrollmentOnlyTempUser->id,
            'enrolled_student_user_id' => $enrollmentOnlyStudent->id,
        ]);
        $enrollmentOnly->accounts()->syncWithoutDetaching([
            $enrollmentOnlyStudent->id => ['account_type' => 'Student'],
            $enrollmentOnlyParent->id => ['account_type' => 'Parent'],
        ]);

        $gradeTenStudent = $this->makeUser('student', 'Charlie Student');
        $gradeTenTemporaryUser = $this->makeUser('enroll', 'Grade Ten Temporary User');
        $gradeTenEnrollment = StudentEnrollment::create([
            'surname' => 'Student',
            'first_name' => 'Charlie',
            'dob' => '2016-03-01',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000002',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'School Address',
            'grade_enrolling' => 'Grade 10',
            'my_class_id' => $gradeTenClass->id,
            'section_id' => $stPeterSection->id,
            'account_username' => $gradeTenTemporaryUser->username,
            'password' => 'TEMP125',
            'status' => 'approved',
            'user_id' => $gradeTenTemporaryUser->id,
            'enrolled_student_user_id' => $gradeTenStudent->id,
        ]);
        $gradeTenEnrollment->accounts()->syncWithoutDetaching([
            $gradeTenStudent->id => ['account_type' => 'Student'],
            $enrollmentOnlyParent->id => ['account_type' => 'Parent'],
        ]);

        BillingList::updateOrCreate(
            ['grade_key' => '3'],
            ['grade_label' => 'Grade 3', 'layout' => json_encode([
                'columns' => [
                    ['key' => 'tuition', 'title' => 'Tuition'],
                    ['key' => 'books', 'title' => 'Books'],
                ],
                'rows' => [
                    ['id' => 'billing_tuition', 'cells' => ['tuition' => 'Tuition Fee', 'books' => '1200.00']],
                    ['id' => 'billing_books', 'cells' => ['tuition' => 'Books Fee', 'books' => '350.00']],
                ],
            ])]
        );
        foreach (['k' => 'Kinder', '1' => 'Grade 1', '2' => 'Grade 2', '10' => 'Grade 10'] as $gradeKey => $gradeLabel) {
            BillingList::updateOrCreate(
                ['grade_key' => $gradeKey],
                ['grade_label' => $gradeLabel, 'layout' => json_encode(['columns' => [], 'rows' => []])]
            );
        }

        $indexResponse = $this->actingAs($accountant)->get('http://localhost/accountant/notices');
        $indexResponse->assertOk()
            ->assertSee('Create Notice')
            ->assertSee('for="notice-class">Class', false)
            ->assertSee('Choose Class')
            ->assertSee('Choose a class first')
            ->assertSee('localeCompare(String(second.name || \'\')', false)
            ->assertSee('Manage Notices')
            ->assertSee('Paid Notices')
            ->assertSee('data-notice-selection="unpaid"', false)
            ->assertSee('data-notice-selection="paid"', false)
            ->assertSee('Select All')
            ->assertSee('Deselect All')
            ->assertSee('Delete Selected')
            ->assertSee('justify-content: flex-end;', false)
            ->assertSee('.notice-selection-toolbar .selection-counter {', false)
            ->assertSee('Grade 3')
            ->assertSee('"key":"tuition","title":"Tuition"', false)
            ->assertSee('"key":"books","title":"Books"', false)
            ->assertSee('"id":"billing_tuition","cells":{"tuition":"Tuition Fee","books":"1200.00"}', false)
            ->assertSee('id="notice-add-row"', false)
            ->assertSee('id="notice-add-column"', false)
            ->assertSee('id="notice-remove-row"', false)
            ->assertSee('id="notice-remove-column"', false)
            ->assertSee('id="undo-notice-change"', false)
            ->assertSee('id="redo-notice-change"', false)
            ->assertSee('function fitFirstColumn()', false)
            ->assertDontSee('width: 220px;', false)
            ->assertSee('vertical-align: middle;', false)
            ->assertSee('outline: none;', false)
            ->assertSee('background: transparent;', false)
            ->assertDontSee('Import billing headings')
            ->assertSee('Send this payment notice to the linked parent account(s)?');

        $gradeOptions = [
            'value="k">Kinder</option>',
            'value="1">Grade 1</option>',
            'value="2">Grade 2</option>',
            'value="3">Grade 3</option>',
            'value="10">Grade 10</option>',
        ];
        $gradePositions = array_map(function ($option) use ($indexResponse) {
            return strpos($indexResponse->getContent(), $option);
        }, $gradeOptions);
        $sortedGradePositions = $gradePositions;
        sort($sortedGradePositions);
        $this->assertSame($sortedGradePositions, $gradePositions);

        $classOptions = [
            'value="' . $kinderClass->id . '">Kinder ' . $sectionOrderMarker . '</option>',
            'value="' . $gradeTwoClass->id . '">Grade 2 ' . $sectionOrderMarker . '</option>',
            'value="' . $class->id . '">' . $className . '</option>',
            'value="' . $gradeTenClass->id . '">' . $gradeTenClassName . '</option>',
            'value="' . $gradeTwelveClass->id . '">' . $gradeTwelveClassName . '</option>',
        ];
        $classPositions = array_map(function ($option) use ($indexResponse) {
            return strpos($indexResponse->getContent(), $option);
        }, $classOptions);
        $sortedClassPositions = $classPositions;
        sort($sortedClassPositions);
        $this->assertSame($sortedClassPositions, $classPositions);
        $indexResponse->assertDontSee('Choose Section')
            ->assertDontSee('Choose a section first');

        $studentsResponse = $this->actingAs($accountant)
            ->get('http://localhost/accountant/notices/classes/' . $class->id . '/students')
            ->assertOk();
        $studentsResponse
            ->assertJsonPath('0.name', 'Student, Alpha')
            ->assertJsonPath('1.name', 'Student, Jamie')
            ->assertJsonFragment(['name' => 'One, Parent'])
            ->assertJsonFragment(['name' => 'Two, Parent']);
        $this->actingAs($accountant)
            ->get('http://localhost/accountant/notices/classes/' . $gradeTwelveClass->id . '/students')
            ->assertOk()
            ->assertJsonPath('0.name', 'Student, Beta');
        $this->actingAs($accountant)
            ->get('http://localhost/accountant/notices/classes/' . $gradeTenClass->id . '/students')
            ->assertOk()
            ->assertJsonPath('0.name', 'Student, Charlie')
            ->assertJsonPath('0.section_name', 'St. Peter');

        $noticeLayout = [
            'columns' => [
                ['key' => 'tuition', 'title' => 'Fee'],
                ['key' => 'books', 'title' => 'Amount'],
            ],
            'rows' => [
                ['id' => 'row_tuition', 'cells' => ['tuition' => 'Tuition', 'books' => '1200.00']],
                ['id' => 'row_books', 'cells' => ['tuition' => 'Books', 'books' => '350.00']],
            ],
        ];
        $this->actingAs($accountant)
            ->post('http://localhost/accountant/notices', [
                'class_id' => $class->id,
                'student_user_id' => $student->id,
                'layout' => $noticeLayout,
            ])
            ->assertRedirect(route('accountant.notices.index') . '#manage-notices');

        $notice = BillingNotice::with('recipients')->latest('id')->firstOrFail();
        $this->assertSame(2, $notice->recipients->count());
        $this->assertSame($className, $notice->student_class);

        $this->actingAs($accountant)
            ->post('http://localhost/accountant/notices', [
                'class_id' => $gradeTwelveClass->id,
                'student_user_id' => $enrollmentOnlyStudent->id,
                'layout' => $noticeLayout,
            ])
            ->assertRedirect(route('accountant.notices.index') . '#manage-notices');
        $enrollmentNotice = BillingNotice::with('recipients')->latest('id')->firstOrFail();
        $this->assertNull($enrollmentNotice->student_record_id);
        $this->assertNull($enrollmentNotice->section_id);
        $this->assertSame([$enrollmentOnlyParent->id], $enrollmentNotice->recipients->pluck('user_id')->all());

        $this->actingAs($firstParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertSee('class="card border-danger mb-3"', false)
            ->assertSee('Good day ' . $firstParent->name . '.')
                ->assertSee('Student, Jamie, ' . $className . ' - Blue')
            ->assertSee('Fee')
            ->assertSee('Amount')
            ->assertSee('Tuition')
            ->assertSee('1200.00')
            ->assertSee('Please go to the school cashier to pay the presented amount.');

        $this->actingAs($secondParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertSee('Good day ' . $secondParent->name . '.')
            ->assertSee('Books')
            ->assertSee('350.00');

        $this->actingAs($enrollmentOnlyParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertSee('Student, Beta, ' . $gradeTwelveClassName)
            ->assertSee('1200.00');

        $this->actingAs($accountant)
            ->get('http://localhost/accountant/notices#manage-notices')
            ->assertOk()
            ->assertSee('Mark Paid')
            ->assertSee('class="notice-select-checkbox"', false)
            ->assertSee('One, Parent, Two, Parent')
            ->assertSee('Mark this notice as paid? This moves it to Paid Notices.');

        $this->actingAs($accountant)
            ->post('http://localhost/accountant/notices/' . $notice->id . '/mark-paid')
            ->assertRedirect(route('accountant.notices.index') . '#manage-notices');

        $notice->refresh();
        $this->assertNotNull($notice->paid_at);
        $this->actingAs($firstParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertSee('class="card border-success mb-3"', false)
            ->assertSee('class="card-header bg-success text-white"', false)
            ->assertSee('<p class="font-weight-bold mb-0 mt-2">Paid on ' . $notice->paid_at->format('M d, Y h:i A') . '.</p>', false);
        $this->actingAs($accountant)
            ->get('http://localhost/accountant/notices#paid-notices')
            ->assertOk()
            ->assertSee($className)
            ->assertSee('One, Parent, Two, Parent')
            ->assertSee($notice->paid_at->format('M d, Y h:i A'));

        $this->actingAs($accountant)
            ->post('http://localhost/accountant/notices/delete-selected', [
                'notice_tab' => 'paid',
                'notice_ids' => [$notice->id, $enrollmentNotice->id],
            ])
            ->assertRedirect(route('accountant.notices.index') . '#paid-notices');
        $this->assertDatabaseMissing('billing_notices', ['id' => $notice->id]);
        $this->assertDatabaseMissing('billing_notices', ['id' => $enrollmentNotice->id]);
        $this->assertDatabaseMissing('billing_notice_recipients', ['billing_notice_id' => $notice->id]);
        $this->assertDatabaseMissing('billing_notice_recipients', ['billing_notice_id' => $enrollmentNotice->id]);
        $this->actingAs($firstParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertDontSee('Student, Jamie, ' . $className . ' - Blue');
        $this->actingAs($enrollmentOnlyParent)
            ->get('http://localhost/inbox')
            ->assertOk()
            ->assertDontSee('Student, Beta, ' . $gradeTwelveClassName);
    }
}
