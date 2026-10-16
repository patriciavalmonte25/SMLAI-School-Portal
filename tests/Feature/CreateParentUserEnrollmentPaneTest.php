<?php

namespace Tests\Feature;

use App\Models\StudentEnrollment;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\StudentGradeSheet;
use App\Models\UserType;
use App\User;
use App\Helpers\Qs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateParentUserEnrollmentPaneTest extends TestCase
{
    public function test_student_my_account_uses_a_profile_information_table()
    {
        $student = User::create([
            'name' => 'Student Profile Example',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'dob' => '2012-03-04',
            'gender' => 'Female',
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        $schoolClass = MyClass::with('section')->firstOrFail();
        $section = $schoolClass->section->first() ?: Section::create([
            'name' => 'Student Profile Section',
            'my_class_id' => $schoolClass->id,
            'active' => 1,
        ]);
        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'adm_no' => 'LRN' . uniqid(),
            'session' => Qs::getCurrentSession(),
            'year_admitted' => '2024',
            'grad' => 0,
        ]);

        $response = $this->actingAs($student)
            ->get('http://localhost/my_account')
            ->assertOk()
            ->assertSee('<table class="table table-bordered">', false)
            ->assertSee('Date of Birth')
            ->assertSee('Class')
            ->assertSee('Section')
            ->assertSee('ADM_NO')
            ->assertSee('Change Password')
            ->assertDontSee('name="email"', false)
            ->assertDontSee('Birthday');

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, '<span>My Account</span>'), strpos($content, '<span>Inbox</span>'));
        $this->assertStringNotContainsString('Academics', $content);

        $classmate = User::create([
            'name' => 'Student Classmate Example',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        StudentRecord::create([
            'user_id' => $classmate->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'adm_no' => 'LRN' . uniqid(),
            'session' => Qs::getCurrentSession(),
            'year_admitted' => '2024',
            'grad' => 0,
        ]);
        StudentGradeSheet::create([
            'student_user_id' => $classmate->id,
            'layout' => ['columns' => [], 'rows' => []],
            'visibility' => 'All Users',
        ]);

        $classRosterResponse = $this->get(route('student.my_class'))
            ->assertOk()
            ->assertSee($schoolClass->name)
            ->assertSee($classmate->name)
            ->assertDontSee('Profile</a>')
            ->assertDontSee('aria-label="Student actions"');
        $this->assertStringNotContainsString(
            route('students.grades.show', Qs::hash($classmate->id)),
            $classRosterResponse->getContent()
        );
        $this->assertStringNotContainsString(
            route('users.show', Qs::hash($classmate->id)),
            $classRosterResponse->getContent()
        );
        $this->get(route('users.show', Qs::hash($classmate->id)))
            ->assertRedirect(route('dashboard'));
    }

    public function test_parent_can_open_profile_and_visible_grades_for_enrollment_linked_student_without_record()
    {
        $parent = User::create([
            'name' => 'Late Link Parent',
            'username' => 'parent-' . uniqid(),
            'email' => 'parent-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);
        $student = User::create([
            'name' => 'Late Link Student',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        $temporaryUser = User::create([
            'name' => 'Temporary Student Requester',
            'username' => 'enroll-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'enroll',
            'code' => strtoupper(uniqid()),
        ]);
        $schoolClass = MyClass::create(['name' => 'Enrollment Class ' . uniqid(), 'class_type_id' => null]);
        $section = Section::create(['name' => 'Section Z', 'my_class_id' => $schoolClass->id, 'active' => 1]);
        $studentEnrollment = StudentEnrollment::create([
            'surname' => 'Student',
            'first_name' => 'Late Link',
            'dob' => '2015-04-10',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000000',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'School Address',
            'grade_enrolling' => $schoolClass->name,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'account_username' => $temporaryUser->username,
            'password' => 'TEMP123',
            'status' => 'approved',
            'user_id' => $temporaryUser->id,
            'enrolled_student_user_id' => $student->id,
        ]);
        $student->parents()->syncWithoutDetaching([$parent->id]);
        StudentGradeSheet::create([
            'student_user_id' => $student->id,
            'layout' => ['columns' => [['key' => 'subject', 'title' => 'Subject'], ['key' => 'grade', 'title' => 'Grade']], 'rows' => []],
            'visibility' => 'All Users',
        ]);
        $this->assertTrue($student->parents()->where('users.id', $parent->id)->exists());
        $this->assertTrue(Qs::userIsMyChild($student->id, $parent->id));

        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('<th>Class</th>', false)
            ->assertSee($schoolClass->name)
            ->assertDontSee($schoolClass->name . ' - ' . $section->name)
            ->assertSee(route('users.show', Qs::hash($student->id)), false)
            ->assertSee(route('students.grades.show', Qs::hash($student->id)), false);

        $this->actingAs($parent)
            ->get(route('users.show', Qs::hash($student->id)))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('<table class="table table-bordered">', false)
            ->assertSee('Date of Birth')
            ->assertDontSee('<td class="font-weight-bold">Name</td>', false)
            ->assertSee('Section')
            ->assertDontSee('Pathway')
            ->assertDontSee('<td class="font-weight-bold">Password</td>', false)
            ->assertDontSee('TEMP123');

        $studentEnrollment->student_grade = 'Grade 11';
        $studentEnrollment->pathway = 'Healthcare and Safety';
        $studentEnrollment->save();

        $this->actingAs($parent)
            ->get(route('users.show', Qs::hash($student->id)))
            ->assertSee('Pathway')
            ->assertSee('Healthcare and Safety');

        $this->actingAs($parent)
            ->get(route('students.grades.show', Qs::hash($student->id)))
            ->assertOk()
            ->assertSee('Student Grades');
    }

    public function test_parent_can_view_only_the_class_lists_of_their_children()
    {
        $parent = User::create([
            'name' => 'Class List Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);
        $firstClass = MyClass::create(['name' => 'Parent Class One ' . uniqid(), 'class_type_id' => null]);
        $secondClass = MyClass::create(['name' => 'Parent Class Two ' . uniqid(), 'class_type_id' => null]);
        $unrelatedClass = MyClass::create(['name' => 'Unrelated Class ' . uniqid(), 'class_type_id' => null]);

        $createStudent = function ($name, $class, $linkToParent = true) use ($parent) {
            $student = User::create([
                'name' => $name,
                'username' => 'student-' . uniqid(),
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(uniqid()),
            ]);

            if ($linkToParent) {
                $student->parents()->syncWithoutDetaching([$parent->id]);
            }

            $section = Section::create([
                'name' => 'A',
                'my_class_id' => $class->id,
                'active' => 1,
            ]);
            StudentRecord::create([
                'user_id' => $student->id,
                'my_class_id' => $class->id,
                'section_id' => $section->id,
                'adm_no' => 'LRN' . uniqid(),
                'session' => Qs::getCurrentSession(),
                'grad' => 0,
            ]);

            return $student;
        };

        $firstChild = $createStudent('First Child', $firstClass);
        $secondChild = $createStudent('Second Child', $secondClass);
        $unrelatedStudent = $createStudent('Unrelated Student', $unrelatedClass, false);

        $classListResponse = $this->actingAs($parent)->get('http://localhost/my-class');
        $classListResponse->assertSee($firstClass->name)
            ->assertSee($secondClass->name)
            ->assertDontSee($unrelatedClass->name);

        $this->actingAs($parent)
            ->get(route('student.my_class', ['class_id' => $firstClass->id]))
            ->assertOk()
            ->assertSee($firstChild->name)
            ->assertDontSee($secondChild->name)
            ->assertDontSee($unrelatedStudent->name);

        $firstChildRecord = StudentRecord::where('user_id', $firstChild->id)->firstOrFail();
        $this->actingAs($parent)
            ->get(route('students.show', Qs::hash($firstChildRecord->id)))
            ->assertOk()
            ->assertDontSee('<td class="font-weight-bold">Name</td>', false);

        $this->actingAs($parent)
            ->get(route('student.my_class', ['class_id' => $unrelatedClass->id]))
            ->assertForbidden();
    }

    public function test_my_children_includes_student_linked_only_through_parent_enrollment_accounts()
    {
        $parent = User::create([
            'name' => 'Enrollment Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);
        $student = User::create([
            'name' => 'Enrollment Child',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        $enrollment = StudentEnrollment::create([
            'surname' => 'Child',
            'first_name' => 'Enrollment',
            'dob' => '2015-04-10',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000000',
            'last_school_name' => 'Sample School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'School Address',
            'grade_enrolling' => 'Grade 1',
            'account_username' => $parent->username,
            'status' => 'approved',
            'user_id' => $parent->id,
            'enrolled_student_user_id' => $student->id,
        ]);
        $enrollment->accounts()->sync([
            $parent->id => ['account_type' => 'Parent'],
            $student->id => ['account_type' => 'Student'],
        ]);

        $this->assertFalse($parent->children()->where('users.id', $student->id)->exists());
        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee(route('users.show', Qs::hash($student->id)), false);
    }

    public function test_parent_children_are_ordered_by_section_creation_order()
    {
        $parent = User::create([
            'name' => 'Section Order Parent',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);
        $class = MyClass::create(['name' => 'Section Order Class', 'class_type_id' => null]);
        $firstSection = Section::create(['name' => 'Z Section', 'my_class_id' => $class->id, 'active' => 1]);
        $secondSection = Section::create(['name' => 'A Section', 'my_class_id' => $class->id, 'active' => 1]);
        $students = collect([
            ['name' => 'Zulu Child', 'section' => $firstSection],
            ['name' => 'Alpha Child', 'section' => $secondSection],
        ])->map(function ($item) use ($class, $parent) {
            $student = User::create([
                'name' => $item['name'],
                'username' => 'student-' . uniqid(),
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(uniqid()),
            ]);
            StudentRecord::create([
                'user_id' => $student->id,
                'my_class_id' => $class->id,
                'section_id' => $item['section']->id,
                'session' => Qs::getCurrentSession(),
                'grad' => 0,
            ]);
            $parent->children()->syncWithoutDetaching([$student->id]);

            return $student;
        });

        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSeeInOrder([
                $students[0]->name,
                $students[1]->name,
            ]);
    }

    public function test_registrar_can_create_multiple_parent_child_links()
    {
        $registrar = User::create([
            'name' => 'Link Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);
        $schoolClass = MyClass::with('section')->firstOrFail();
        $section = $schoolClass->section->first() ?: Section::create([
            'name' => 'Parent Link Section',
            'my_class_id' => $schoolClass->id,
            'active' => 1,
        ]);
        $students = collect(['Child One', 'Child Two'])->map(function ($name) use ($schoolClass, $section) {
            $student = User::create([
                'name' => $name,
                'username' => 'student-' . uniqid(),
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(uniqid()),
            ]);
            StudentRecord::create([
                'user_id' => $student->id,
                'my_class_id' => $schoolClass->id,
                'section_id' => $section->id,
                'session' => Qs::getCurrentSession(),
                'grad' => 0,
            ]);
            return $student;
        });
        $parentType = UserType::where('title', 'parent')->firstOrFail();

        $createParent = function ($name, $children) use ($registrar, $parentType) {
            $this->actingAs($registrar)
                ->post('http://localhost/users', [
                    'user_type' => Qs::hash($parentType->id),
                    'name' => $name,
                    'address' => 'Parent Address',
                    'children' => $children,
                ])
                ->assertOk()
                ->assertJsonPath('ok', true);

            return User::where('user_type', 'parent')->latest('id')->firstOrFail();
        };

        $firstParent = $createParent('First Parent Account', $students->map(function ($student) use ($schoolClass) {
            return ['class_id' => $schoolClass->id, 'student_id' => Qs::hash($student->id)];
        })->all());
        $secondParent = $createParent('Second Parent Account', [[
            'class_id' => $schoolClass->id,
            'student_id' => Qs::hash($students->first()->id),
        ]]);

        $this->assertSame(2, $firstParent->children()->count());
        $this->assertTrue($students->first()->parents()->where('users.id', $firstParent->id)->exists());
        $this->assertTrue($students->first()->parents()->where('users.id', $secondParent->id)->exists());
        $this->assertTrue($students->last()->parents()->where('users.id', $firstParent->id)->exists());
        $this->assertFalse($students->last()->parents()->where('users.id', $secondParent->id)->exists());
    }

    public function test_registrar_can_edit_parent_details_and_replace_multiple_children()
    {
        $registrar = User::create([
            'name' => 'Parent Profile Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $parent = User::create([
            'name' => 'Parent Original',
            'username' => 'parent-' . uniqid(),
            'email' => 'parent-' . uniqid() . '@example.com',
            'phone' => '09170000000',
            'address' => 'Original Address',
            'occupation' => 'Original Occupation',
            'relationship_to_children' => 'Mother',
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);

        $schoolClass = MyClass::with('section')->firstOrFail();
        $section = $schoolClass->section->first() ?: Section::create([
            'name' => 'Parent Profile Section',
            'my_class_id' => $schoolClass->id,
            'active' => 1,
        ]);

        $students = collect(['First Child', 'Second Child'])->map(function ($name) use ($schoolClass, $section) {
            $student = User::create([
                'name' => $name,
                'username' => 'student-' . uniqid(),
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(uniqid()),
            ]);

            return StudentRecord::create([
                'user_id' => $student->id,
                'my_class_id' => $schoolClass->id,
                'section_id' => $section->id,
                'session' => Qs::getCurrentSession(),
                'grad' => 0,
            ]);
        });
        $otherParent = User::create([
            'name' => 'Parent Other',
            'username' => 'parent-' . uniqid(),
            'email' => 'parent-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);
        $otherParent->children()->syncWithoutDetaching([$students->first()->user_id]);

        $oldChild = User::create([
            'name' => 'Old Child',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        $oldChildRecord = StudentRecord::create([
            'user_id' => $oldChild->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'session' => Qs::getCurrentSession(),
            'my_parent_id' => $parent->id,
            'grad' => 0,
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($parent->id))
            ->assertOk()
            ->assertSee('Children')
            ->assertSee('Child, Old')
            ->assertSee('<ul class="mb-0 pl-3">', false)
            ->assertSee('<li>', false)
            ->assertSee($schoolClass->name)
            ->assertSee('Date of Birth')
            ->assertSee('Gender')
            ->assertDontSee('Birthday');

        $this->actingAs($registrar)
            ->get('http://localhost/users')
            ->assertOk()
            ->assertSee('Grades of Child/Children', false)
            ->assertSee('Child, Old');

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($parent->id) . '/edit')
            ->assertOk()
            ->assertSee('Parent Information')
            ->assertSee('Relationship to Children')
            ->assertSee('Children')
            ->assertSee('Select student');

        $updatedEmail = $parent->email;
        $updatedUsername = $parent->username;

        $this->actingAs($registrar)
            ->put('http://localhost/users/' . Qs::hash($parent->id), [
                'name' => 'Parent Updated',
                'email' => $updatedEmail,
                'phone' => '09171112222',
                'address' => 'Updated Address',
                'occupation' => 'Updated Occupation',
                'dob' => '1980-04-12',
                'gender' => 'Female',
                'relationship_to_children' => 'Guardian',
                'username' => $updatedUsername,
                'password' => 'NewPass2026!',
                'children' => $students->map(function ($record) {
                    return [
                        'class_id' => $record->my_class_id,
                        'student_id' => Qs::hash($record->user_id),
                    ];
                })->all(),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $parent->refresh();
        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($parent->id))
            ->assertOk()
            ->assertSee('<ul class="mb-0 pl-3">', false)
            ->assertSee('class="font-weight-semibold text-primary"', false)
            ->assertSee('href="' . route('students.show', Qs::hash($students->first()->id)) . '"', false);

        $studentProfileUrl = 'http://localhost/users/' . Qs::hash($students->first()->user_id);
        $this->actingAs($registrar)
            ->get($studentProfileUrl)
            ->assertOk()
            ->assertSee('Parents/Guardians')
            ->assertSee('Updated, Parent')
            ->assertSee('Other, Parent')
            ->assertSee('href="' . route('users.show', Qs::hash($parent->id)) . '"', false);

        $unassignedTeacher = User::create([
            'name' => 'Unassigned Teacher',
            'username' => 'teacher-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'teacher',
            'code' => strtoupper(uniqid()),
        ]);
        $this->actingAs($unassignedTeacher)
            ->get($studentProfileUrl)
            ->assertOk()
            ->assertSee('Parents/Guardians')
            ->assertSee('Updated, Parent');
        $this->actingAs($unassignedTeacher)
            ->get('http://localhost/students/' . Qs::hash($students->first()->id))
            ->assertOk()
            ->assertSee('Parents/Guardians')
            ->assertSee('Other, Parent');

        $this->actingAs($registrar)
            ->get('http://localhost/students/' . Qs::hash($students->first()->id))
            ->assertOk()
            ->assertSee('Parents/Guardians')
            ->assertSee('Other, Parent')
            ->assertSee('href="' . route('users.show', Qs::hash($otherParent->id)) . '"', false);
        $accountant = User::create([
            'name' => 'School Accountant',
            'username' => 'accountant-' . uniqid(),
            'email' => 'accountant-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'accountant',
            'code' => strtoupper(uniqid()),
        ]);
        $this->actingAs($accountant)
            ->get($studentProfileUrl)
            ->assertOk()
            ->assertSee('Parents/Guardians')
            ->assertSee('Updated, Parent');

        $this->actingAs($parent)
            ->get('http://localhost/students/' . Qs::hash($students->first()->id))
            ->assertOk()
            ->assertDontSee('Parents/Guardians');
        $this->actingAs($parent)
            ->get('http://localhost/students/' . Qs::hash($oldChildRecord->id))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($students->first()->user)
            ->get($studentProfileUrl)
            ->assertOk()
            ->assertDontSee('Parents/Guardians');
        $this->actingAs($students->first()->user)
            ->get('http://localhost/users/' . Qs::hash($students->last()->user_id))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Updated, Parent', $parent->name);
        $this->assertSame($updatedEmail, $parent->email);
        $this->assertSame($updatedUsername, $parent->username);
        $this->assertSame('09171112222', $parent->phone);
        $this->assertSame('Updated Address', $parent->address);
        $this->assertSame('Updated Occupation', $parent->occupation);
        $this->assertSame('1980-04-12', $parent->dob);
        $this->assertSame('Female', $parent->gender);
        $this->assertSame('Guardian', $parent->relationship_to_children);
        $this->assertTrue(Hash::check('NewPass2026!', $parent->password));
        $this->assertSame('NewPass2026!', decrypt(DB::table('users')->where('id', $parent->id)->value('password_encrypted')));
        $this->assertNull($oldChildRecord->fresh()->my_parent_id);
        $this->assertSame(2, StudentRecord::where('my_parent_id', $parent->id)->count());
        $this->assertSame(2, $parent->children()->count());
        $this->assertTrue($otherParent->children()->where('users.id', $students->first()->user_id)->exists());
        $this->assertFalse($parent->children()->where('users.id', $oldChild->id)->exists());
        StudentGradeSheet::updateOrCreate(
            ['student_user_id' => $students->first()->user_id],
            ['layout' => ['columns' => [], 'rows' => []], 'visibility' => 'All Users']
        );

        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee('.dataTables_wrapper .dataTables_paginate', false)
            ->assertSee('v=20261008-no-table-controls', false)
            ->assertDontSee('<th>S/N</th>', false)
            ->assertDontSee('<th>Email</th>', false)
            ->assertSee('<th>LRN</th>', false)
            ->assertDontSee('<th>ADM_No</th>', false)
            ->assertSee('style="width:56px; min-width:56px; white-space:nowrap;"', false)
            ->assertSee('Child, First')
            ->assertSee('Child, Second')
            ->assertSee(route('students.grades.show', Qs::hash($students->first()->user_id)), false)
            ->assertDontSee('Child, Old');
        $this->actingAs($parent)
            ->get('http://localhost/users/' . Qs::hash($oldChild->id))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($students->first()->user)
            ->get('http://localhost/users/' . Qs::hash($students->last()->user_id))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($otherParent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee('Child, First');

        $this->actingAs($registrar)
            ->put('http://localhost/users/' . Qs::hash($parent->id), [
                'name' => 'Parent Updated',
                'children_present' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(0, $parent->fresh()->children()->count());
        $this->assertTrue($otherParent->children()->where('users.id', $students->first()->user_id)->exists());
        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertDontSee('Child, First')
            ->assertDontSee('Child, Second');

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($parent->id))
            ->assertOk()
            ->assertSee('NewPass2026!')
            ->assertSee('Female')
            ->assertSee('Date of Birth');
    }

    public function test_parent_create_page_lists_temporary_accounts_and_their_enrollment_details()
    {
        $registrar = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => 'SUPER' . uniqid(),
        ]);

        $temporaryUser = User::create([
            'name' => 'Juan Dela Cruz',
            'username' => 'ENROLL' . uniqid(),
            'password' => bcrypt('temp-password'),
            'user_type' => 'enroll',
            'code' => 'ENR' . uniqid(),
        ]);

        $enrollment = StudentEnrollment::create([
            'surname' => 'Dela Cruz',
            'first_name' => 'Juan',
            'dob' => '2013-02-14',
            'place_of_birth_town' => 'Sta. Maria',
            'gender' => 'Male',
            'religion' => 'Christian',
            'home_town' => 'Sta. Maria',
            'father_name' => 'Pedro Dela Cruz',
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

        $this->actingAs($registrar)
            ->get('http://localhost/users/create?type=parent&enrollment_id=' . $enrollment->id)
            ->assertStatus(200)
            ->assertSee('data-success-popup="true"', false)
            ->assertSee('data-confirm-label="Create Account"', false)
            ->assertSee('Enrollment Details')
            ->assertSee('Dela Cruz, Juan')
            ->assertSee('id="temp-enrollment-select" name="source_enrollment_id"', false)
            ->assertSee('value="' . $enrollment->id . '" selected', false)
            ->assertSee('data-enrollment-id="' . $enrollment->id . '" style="display:block;', false)
            ->assertSee('Pedro Dela Cruz')
            ->assertDontSee('This notice is to confirm that the enrollment application of')
            ->assertSee('Date of Birth:')
            ->assertSee('Gender:')
            ->assertSee('name="dob"', false)
            ->assertSee('type="text" class="form-control date-pick" placeholder="MM/DD/YYYY" autocomplete="off" data-date-format="mm/dd/yyyy"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Sample School');

        $this->actingAs($registrar)
            ->get('http://localhost/users/create?type=parent')
            ->assertOk()
            ->assertSee('id="temp-enrollment-select" name="source_enrollment_id"', false)
            ->assertSee('value="' . $enrollment->id . '"', false);

        $this->actingAs($registrar)
            ->get('http://localhost/users/create?type=teacher')
            ->assertStatus(200)
            ->assertDontSee('Enrollment Details');

        $parentType = UserType::where('title', 'parent')->firstOrFail();
        $parentUsername = 'PARENT' . uniqid();
        $this->actingAs($registrar)
            ->post('http://localhost/users', [
                'user_type' => Qs::hash($parentType->id),
                'source_enrollment_id' => $enrollment->id,
                'name' => 'Juan Dela Cruz Parent',
                'address' => 'Sta. Maria, Laguna',
                'username' => $parentUsername,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('msg', 'Parent account created successfully.');

        $parentAccount = User::where('username', $parentUsername)->firstOrFail();
        $parentPassword = decrypt($parentAccount->password_encrypted);
        $this->assertSame(8, strlen($parentPassword));
        $this->assertMatchesRegularExpression('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8}$/', $parentPassword);
        $this->assertSame('Parent', $enrollment->fresh()->accounts()->where('users.id', $parentAccount->id)->first()->pivot->account_type);
        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/outbox')
            ->assertOk()
            ->assertSee($parentUsername, false);

        $this->actingAs($registrar)
            ->from('http://localhost/users/create?type=parent&enrollment_id=' . $enrollment->id)
            ->post('http://localhost/users', [
                'user_type' => Qs::hash($parentType->id),
                'source_enrollment_id' => $enrollment->id,
                'name' => 'Another Parent Account',
                'address' => 'Sta. Maria, Laguna',
                'username' => $parentUsername,
            ])
            ->assertSessionHasErrors([
                'username' => 'An account already exists for ' . $parentAccount->name . ' with username ' . $parentUsername . '.',
            ]);
    }
}
