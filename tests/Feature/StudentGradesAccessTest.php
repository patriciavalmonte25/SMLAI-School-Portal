<?php

namespace Tests\Feature;

use App\Models\StudentEnrollment;
use App\Models\StudentGradeSheet;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Helpers\Qs;
use App\User;
use Tests\TestCase;

class StudentGradesAccessTest extends TestCase
{
    private function makeUser($type, $name = null)
    {
        $username = $type . '-' . uniqid();

        return User::create([
            'name' => $name ?: ucfirst($type) . ' Account',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($type) . uniqid(),
        ]);
    }

    private function makeLinkedStudentAndParent()
    {
        $student = $this->makeUser('student', 'Student Example');
        $parent = $this->makeUser('parent', 'Parent Example');

        $enrollment = StudentEnrollment::create([
            'surname' => 'Example',
            'first_name' => 'Student',
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
            'status' => 'approved',
            'user_id' => $student->id,
        ]);
        $enrollment->accounts()->syncWithoutDetaching([
            $student->id => ['account_type' => 'Student'],
            $parent->id => ['account_type' => 'Parent'],
        ]);

        return [$student, $parent];
    }

    public function test_visibility_and_grade_changes_follow_role_and_family_rules()
    {
        $administrator = $this->makeUser('administrator');
        $teacher = $this->makeUser('teacher');
        $studentParent = $this->makeLinkedStudentAndParent();
        $student = $studentParent[0];
        $parent = $studentParent[1];
        $otherStudent = $this->makeUser('student', 'Other Student');
        $unrelatedParent = $this->makeUser('parent', 'Unrelated Parent');
        $gradesUrl = 'http://localhost/students/' . \App\Helpers\Qs::hash($student->id) . '/grades';

        $this->actingAs($administrator)
            ->get($gradesUrl)
            ->assertOk()
            ->assertSee('Teaching Staff')
            ->assertSee('Save Visibility')
            ->assertSee('Edit')
            ->assertDontSee('Add Row')
            ->assertDontSee('Add Column')
            ->assertDontSee('Undo')
            ->assertDontSee('Redo')
            ->assertDontSee('Save Progress')
            ->assertSee('No grades have been entered.');

        $this->actingAs($administrator)
            ->get($gradesUrl . '/edit')
            ->assertOk()
            ->assertSee('Add Row')
            ->assertSee('Add Column')
            ->assertSee('Remove Row')
            ->assertSee('Remove Column')
            ->assertSee('Undo')
            ->assertSee('Redo')
            ->assertSee('Save Progress');

        $this->actingAs($administrator)
            ->post($gradesUrl . '/layout', [
                'layout' => [
                    'columns' => [
                        ['key' => 'subject', 'title' => 'Subject'],
                        ['key' => 'grade', 'title' => 'Grade'],
                    ],
                    'rows' => [],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($parent)
            ->get($gradesUrl)
            ->assertOk()
            ->assertSee('Teaching Staff')
            ->assertSee('No grades have been entered.');
        $this->actingAs($student)->get($gradesUrl)->assertForbidden();
    $this->actingAs($parent)->get($gradesUrl . '/edit')->assertForbidden();
    $this->actingAs($student)->get($gradesUrl . '/edit')->assertForbidden();

        $this->actingAs($teacher)
            ->post($gradesUrl . '/visibility', ['visibility' => 'All Users'])
            ->assertOk()
            ->assertJsonPath('visibility', 'All Users');

        $this->actingAs($teacher)
            ->get($gradesUrl)
            ->assertOk()
            ->assertSee('Edit')
            ->assertDontSee('Add Row')
            ->assertDontSee('Add Column')
            ->assertDontSee('Save Progress');

        $this->actingAs($parent)
            ->get($gradesUrl)
            ->assertOk()
            ->assertDontSee('Edit')
            ->assertDontSee('Add Row')
            ->assertDontSee('Add Column')
            ->assertDontSee('Undo')
            ->assertDontSee('Redo')
            ->assertDontSee('Save Progress')
            ->assertDontSee('Save Visibility');

        $layout = [
            'columns' => [
                ['key' => 'subject', 'title' => 'Subject'],
                ['key' => 'grade', 'title' => 'Grade'],
            ],
            'rows' => [
                ['id' => 'row_1', 'cells' => ['subject' => 'Mathematics', 'grade' => 'A']],
            ],
        ];

        $this->actingAs($teacher)
            ->get($gradesUrl . '/edit')
            ->assertOk()
            ->assertSee('Add Row')
            ->assertSee('Add Column')
            ->assertSee('Remove Row')
            ->assertSee('Remove Column')
            ->assertSee('Save Progress');

        $this->actingAs($teacher)
            ->post($gradesUrl . '/layout', ['layout' => $layout])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($parent)
            ->post($gradesUrl . '/layout', ['layout' => $layout])
            ->assertForbidden();

        $this->actingAs($student)
            ->get($gradesUrl)
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('"grade":"A"', false)
            ->assertDontSee('Add Row')
            ->assertDontSee('Add Column')
            ->assertDontSee('Save Progress');

        StudentGradeSheet::create([
            'student_user_id' => $otherStudent->id,
            'layout' => $layout,
            'visibility' => 'All Users',
        ]);
        $this->actingAs($student)
            ->get('http://localhost/students/' . Qs::hash($otherStudent->id) . '/grades')
            ->assertForbidden();

        $this->actingAs($unrelatedParent)->get($gradesUrl)->assertForbidden();

        $this->assertSame('All Users', StudentGradeSheet::where('student_user_id', $student->id)->value('visibility'));
    }

    public function test_unshared_grades_links_are_hidden_from_parent_and_registrar_interfaces()
    {
        $registrar = $this->makeUser('registrar');
        $linked = $this->makeLinkedStudentAndParent();
        $student = $linked[0];
        $parent = $linked[1];
        $schoolClass = MyClass::with('section')->firstOrFail();
        $section = $schoolClass->section->first() ?: Section::create([
            'name' => 'Grades Visibility Section',
            'my_class_id' => $schoolClass->id,
            'active' => 1,
        ]);

        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'session' => Qs::getCurrentSession(),
            'grad' => 0,
        ]);
        $student->parents()->syncWithoutDetaching([$parent->id]);

        StudentGradeSheet::firstOrCreate(
            ['student_user_id' => $student->id],
            ['layout' => ['columns' => [], 'rows' => []], 'visibility' => 'Teaching Staff']
        );

        $gradePath = '/students/' . Qs::hash($student->id) . '/grades';

        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee($gradePath, false);

        $this->actingAs($registrar)
            ->get('http://localhost/super_admin/classes-students/' . $schoolClass->id)
            ->assertOk()
            ->assertDontSee($gradePath, false)
            ->assertSee(route('users.show', Qs::hash($student->id)), false)
            ->assertDontSee('aria-label="Student actions"');

        StudentGradeSheet::where('student_user_id', $student->id)->update(['visibility' => 'All Users']);

        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertSee($gradePath, false);

        $this->actingAs($parent)->get('http://localhost' . $gradePath)->assertOk();
        $registrar = $this->makeUser('registrar');
        $this->actingAs($registrar)
            ->put('http://localhost/users/' . Qs::hash($parent->id), [
                'name' => $parent->name,
                'children_present' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($parent)
            ->get('http://localhost' . $gradePath)
            ->assertForbidden();
        $this->actingAs($parent)
            ->get('http://localhost/my_children')
            ->assertOk()
            ->assertDontSee($gradePath, false);
    }
}
