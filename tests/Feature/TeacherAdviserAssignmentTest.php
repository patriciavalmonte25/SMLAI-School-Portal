<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\UserType;
use App\User;
use Tests\TestCase;

class TeacherAdviserAssignmentTest extends TestCase
{
    public function test_registrar_can_assign_teacher_as_class_adviser_and_rosters_show_them()
    {
        $registrar = $this->makeUser('registrar', 'Adviser Registrar');
        $schoolClass = MyClass::create(['name' => 'Adviser Class ' . uniqid()]);
        $section = Section::create([
            'name' => 'Adviser Section ' . uniqid(),
            'my_class_id' => $schoolClass->id,
            'active' => 1,
        ]);
        $teacherType = UserType::where('title', 'teacher')->firstOrFail();

        $createResponse = $this->actingAs($registrar)->get('http://localhost/users/create?type=teacher');
        $this->assertSame(200, $createResponse->status(), substr(strip_tags($createResponse->getContent()), 0, 350));
        $createResponse
            ->assertSee('Adviser')
            ->assertSee('None')
            ->assertSee($schoolClass->name)
            ->assertDontSee('name="occupation"', false)
            ->assertDontSee('Role/s:')
            ->assertDontSee('name="roles[]"', false);

        $lastUserId = User::max('id') ?: 0;
        $createResult = $this->post('http://localhost/users', [
            'user_type' => Qs::hash($teacherType->id),
            'name' => 'Assigned Class Adviser',
            'address' => 'School Address',
            'adviser_class_id' => $schoolClass->id,
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('url', route('users.index', ['tab' => 'teacher']));

        $teacher = User::where('user_type', 'teacher')->where('id', '>', $lastUserId)->firstOrFail();
        $this->actingAs($registrar)
            ->get('http://localhost/users?tab=teacher')
            ->assertOk()
            ->assertSee('Teachers')
            ->assertSee($teacher->name)
            ->assertSee('<div class="tab-pane fade show active" id="ut-teacher">', false);

        $teacher->occupation = 'Legacy occupation';
        $teacher->save();
        $this->assertDatabaseHas('my_classes', [
            'id' => $schoolClass->id,
            'adviser_user_id' => $teacher->id,
        ]);
        $this->assertNull($section->fresh()->teacher_id);

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($teacher->id) . '/edit')
            ->assertOk()
            ->assertSee('Adviser')
            ->assertSee('value="' . $schoolClass->id . '" selected', false)
            ->assertDontSee('name="occupation"', false)
            ->assertDontSee('Role/s:');

        $this->get('http://localhost/users/' . Qs::hash($teacher->id))
            ->assertOk()
            ->assertDontSee('Occupation')
            ->assertDontSee('<td>Roles</td>', false);
        $this->actingAs($teacher)
            ->get('http://localhost/my_account')
            ->assertOk()
            ->assertDontSee('Occupation')
            ->assertDontSee('<td>Roles</td>', false);

        $student = $this->makeUser('student', 'Student In Adviser Class');
        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $schoolClass->id,
            'section_id' => $section->id,
            'session' => Qs::getCurrentSession(),
            'grad' => 0,
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/super_admin/classes-students/' . $schoolClass->id)
            ->assertOk()
            ->assertSee('Class Adviser')
            ->assertSee($teacher->name)
            ->assertSee('p-2 border rounded', false)
            ->assertSee('href="' . route('users.show', Qs::hash($teacher->id)) . '" class="user-profile-link">' . $teacher->name . '</a>', false)
            ->assertDontSee('<u>' . $teacher->name . '</u>', false);

        $this->actingAs($teacher)
            ->get('http://localhost/students/list/' . $schoolClass->id)
            ->assertOk()
            ->assertSee('Class Adviser')
            ->assertSee($teacher->name)
            ->assertSee('p-2 border rounded', false)
            ->assertSee('href="' . route('users.show', Qs::hash($teacher->id)) . '" class="user-profile-link">' . $teacher->name . '</a>', false)
            ->assertDontSee('<u>' . $teacher->name . '</u>', false);

        $this->actingAs($student)
            ->get('http://localhost/my-class')
            ->assertOk()
            ->assertSee('Class Adviser')
            ->assertSee($teacher->name);
    }

    private function makeUser($type, $name)
    {
        $username = $type . '-' . uniqid();

        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($username),
        ]);
    }
}