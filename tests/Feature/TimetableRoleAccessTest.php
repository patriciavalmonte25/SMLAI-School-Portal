<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudentRecord;
use App\Models\TimeTableRecord;
use App\Models\TimeTable;
use App\Models\TimeSlot;
use App\User;
use Tests\TestCase;

class TimetableRoleAccessTest extends TestCase
{
    private function makeUser($type)
    {
        $username = $type . '-' . uniqid();

        return User::create([
            'name' => ucfirst($type) . ' ' . uniqid(),
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => $type,
            'code' => strtoupper($username),
        ]);
    }

    private function makeClass()
    {
        return MyClass::create(['name' => 'Timetable Class ' . uniqid()]);
    }

    private function makeStudentInClass(User $student, MyClass $schoolClass)
    {
        $section = Section::create([
            'name' => 'Section ' . uniqid(),
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
    }

    private function makeTimetable($name, $targetType, $schoolClass = null, $owner = null)
    {
        return TimeTableRecord::create([
            'name' => $name . ' ' . uniqid(),
            'target_type' => $targetType,
            'my_class_id' => $schoolClass ? $schoolClass->id : null,
            'owner_user_id' => $owner ? $owner->id : null,
            'year' => Qs::getCurrentSession(),
            'timetable_layout' => json_encode([[
                'id' => uniqid('row_'),
                'type' => 'subject',
                'time_start_hour' => '08',
                'time_start_minute' => '00',
                'time_end_hour' => '09',
                'time_end_minute' => '00',
                'monday_subject' => 'Mathematics',
            ]]),
        ]);
    }

    public function test_students_see_only_their_class_and_staff_timetable_views()
    {
        $student = $this->makeUser('student');
        $ownClass = $this->makeClass();
        $otherClass = $this->makeClass();
        $this->makeStudentInClass($student, $ownClass);
        $ownClassTimetable = $this->makeTimetable('Own Class', 'class', $ownClass);
        $otherClassTimetable = $this->makeTimetable('Other Class', 'class', $otherClass);
        $teacherTimetable = $this->makeTimetable('Teacher Schedule', 'teacher', null, $this->makeUser('teacher'));
        $administratorTimetable = $this->makeTimetable('Administrator Schedule', 'teacher', null, $this->makeUser('admin'));

        $this->actingAs($student)
            ->get('http://localhost/timetables')
            ->assertSee('My Class Timetable')
            ->assertSee('Administrator and Teacher Timetables')
                ->assertSee('Timetables')
                ->assertSee($ownClass->name)
            ->assertSee($teacherTimetable->owner->name)
            ->assertSee($administratorTimetable->owner->name)
            ->assertDontSee($otherClassTimetable->name)
            ->assertDontSee('Create Timetable');

        $this->get('http://localhost/timetables/records/show/' . $ownClassTimetable->id)
            ->assertOk()
            ->assertSee('min-width: 1200px', false)
            ->assertSee('padding: 14px 18px', false);
        $this->get('http://localhost/timetables/records/show/' . $teacherTimetable->id)->assertOk();
        $this->get('http://localhost/timetables/records/show/' . $administratorTimetable->id)->assertOk();
        $this->get('http://localhost/timetables/records/show/' . $otherClassTimetable->id)->assertForbidden();
        $this->post('http://localhost/timetables/records', [])->assertRedirect(route('login'));

        $administrator = $this->makeUser('administrator');
        $this->actingAs($administrator)
            ->get('http://localhost/timetables/records/manage/' . $ownClassTimetable->id)
            ->assertOk()
            ->assertSee('Fill from an existing Timetable')
            ->assertSee($otherClassTimetable->name)
            ->assertDontSee($teacherTimetable->name);
    }

    public function test_parents_see_only_their_children_class_timetables()
    {
        $parent = $this->makeUser('parent');
        $child = $this->makeUser('student');
        $unrelatedStudent = $this->makeUser('student');
        $childClass = $this->makeClass();
        $unrelatedClass = $this->makeClass();
        $this->makeStudentInClass($child, $childClass);
        $this->makeStudentInClass($unrelatedStudent, $unrelatedClass);
        $parent->children()->syncWithoutDetaching([$child->id]);
        $childTimetable = $this->makeTimetable('Child Schedule', 'class', $childClass);
        $unrelatedTimetable = $this->makeTimetable('Unrelated Schedule', 'class', $unrelatedClass);

        $this->actingAs($parent)
            ->get('http://localhost/timetables')
            ->assertOk()
            ->assertSee($child->name)
            ->assertSee($childClass->name)
                ->assertSee('Timetables')
            ->assertDontSee($unrelatedStudent->name)
            ->assertDontSee($unrelatedTimetable->name)
            ->assertDontSee('Create Timetable');

        $this->get('http://localhost/timetables/records/show/' . $childTimetable->id)->assertOk();
        $this->get('http://localhost/timetables/records/show/' . $unrelatedTimetable->id)->assertForbidden();
    }

    public function test_teachers_can_edit_classes_and_their_own_timetable_only()
    {
        $teacher = $this->makeUser('teacher');
        $otherTeacher = $this->makeUser('teacher');
        $schoolClass = $this->makeClass();
        $templateClass = $this->makeClass();
        $classTimetable = $this->makeTimetable('Class Schedule', 'class', $schoolClass);
        $classTemplate = $this->makeTimetable('Class Template', 'class', $templateClass);
        $ownTimetable = $this->makeTimetable('Own Schedule', 'teacher', null, $teacher);
        $otherTimetable = $this->makeTimetable('Other Schedule', 'teacher', null, $otherTeacher);
        $legacyTemplate = $this->makeTimetable('Legacy Template', 'class', $templateClass);
        $legacyTemplate->timetable_layout = null;
        $legacyTemplate->save();
        $legacySlot = TimeSlot::create([
            'ttr_id' => $legacyTemplate->id,
            'hour_from' => 8,
            'min_from' => '00',
            'meridian_from' => 'AM',
            'hour_to' => 9,
            'min_to' => '00',
            'meridian_to' => 'AM',
            'time_from' => '08:00',
            'time_to' => '09:00',
            'timestamp_from' => '28800',
            'timestamp_to' => '32400',
            'full' => '08:00 - 09:00',
        ]);
        TimeTable::create([
            'ttr_id' => $legacyTemplate->id,
            'ts_id' => $legacySlot->id,
            'subject_id' => null,
            'day' => 'Monday',
            'timestamp_from' => '28800',
            'timestamp_to' => '32400',
        ]);

        $this->actingAs($teacher)
            ->get('http://localhost/timetables')
            ->assertOk()
            ->assertSee('Create Timetable')
                ->assertSee('timetable-create-form', false)
            ->assertSee(route('ttr.manage', $classTimetable->id), false)
            ->assertSee(route('ttr.manage', $ownTimetable->id), false)
                ->assertDontSee(route('ttr.manage', $otherTimetable->id), false)
                ->assertSee(route('ttr.show', $otherTimetable->id), false);

        $this->get('http://localhost/timetables/records/manage/' . $classTimetable->id)
            ->assertOk()
            ->assertSee('#timetable-layout-table td', false)
            ->assertSee('padding: 6px 8px', false)
            ->assertSee('min-height: 38px', false)
            ->assertSee('min-width: 1090px', false)
            ->assertSee('width:140px', false)
            ->assertSee('width:190px', false)
            ->assertSee('id="undo-timetable-change"', false)
            ->assertSee('id="redo-timetable-change"', false)
            ->assertSee('id="remove-timetable-row"', false)
            ->assertSee($classTemplate->name)
            ->assertSee('legacy_' . $legacySlot->id)
            ->assertDontSee($ownTimetable->name)
            ->assertDontSee($otherTimetable->name);
        $this->get('http://localhost/timetables/records/manage/' . $ownTimetable->id)
            ->assertOk()
            ->assertSee($otherTimetable->name)
            ->assertSee('id="timetable-title"', false);
        $this->get('http://localhost/timetables/records/manage/' . $otherTimetable->id)->assertForbidden();
        $this->get('http://localhost/timetables/records/show/' . $otherTimetable->id)->assertOk();

        $creator = $this->makeUser('teacher');
        $this->actingAs($creator)->post('http://localhost/timetables/records', [
            'target_type' => 'teacher',
            'owner_user_id' => $otherTeacher->id,
        ])->assertStatus(302);

        $this->assertDatabaseHas('time_table_records', [
            'target_type' => 'teacher',
            'owner_user_id' => $creator->id,
        ]);

        $administrator = $this->makeUser('administrator');
        $this->actingAs($administrator)
            ->get('http://localhost/timetables')
            ->assertOk()
            ->assertSee('Create Timetable')
            ->assertSee(route('ttr.manage', $otherTimetable->id), false);
        $this->get('http://localhost/timetables/records/manage/' . $otherTimetable->id)->assertOk();

        $pathwayName = $schoolClass->name . ' STEM';
        $this->post('http://localhost/timetables/records', [
            'target_type' => 'class',
            'my_class_id' => $schoolClass->id,
        ])->assertStatus(302);
        $this->assertDatabaseHas('time_table_records', [
            'name' => $schoolClass->name,
            'my_class_id' => $schoolClass->id,
        ]);
        $this->post('http://localhost/timetables/records', [
            'target_type' => 'class',
            'my_class_id' => $schoolClass->id,
        ])->assertStatus(302);
        $this->assertDatabaseHas('time_table_records', [
            'name' => $schoolClass->name . ' (2)',
            'my_class_id' => $schoolClass->id,
        ]);
        $this->postJson('http://localhost/timetables/records/layout/' . $classTimetable->id, [
            'title' => $pathwayName,
            'rows' => [[
                'id' => 'pathway-row',
                'type' => 'subject',
                'time_start_hour' => 8,
                'time_start_minute' => '00',
                'time_end_hour' => 9,
                'time_end_minute' => '00',
                'monday_subject' => 'Mathematics',
            ]],
        ])->assertOk();

        $customTitle = 'Grade 11 STEM Timetable ' . uniqid();
        $this->actingAs($teacher)
            ->postJson('http://localhost/timetables/records/layout/' . $ownTimetable->id, [
            'title' => $customTitle,
                'rows' => [[
                    'id' => 'custom-row',
                    'type' => 'subject',
                    'time_start_hour' => 8,
                    'time_start_minute' => '00',
                    'time_end_hour' => 9,
                    'time_end_minute' => '00',
                    'monday_subject' => 'Mathematics',
                ]],
            ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('time_table_records', [
            'id' => $ownTimetable->id,
            'name' => $customTitle,
        ]);

        $this->actingAs($teacher)
            ->postJson('http://localhost/timetables/records/layout/' . $ownTimetable->id, [
                'title' => $customTitle,
                'rows' => [],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertSame('[]', $ownTimetable->fresh()->timetable_layout);
    }
}