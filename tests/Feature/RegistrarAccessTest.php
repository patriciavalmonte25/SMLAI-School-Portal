<?php

namespace Tests\Feature;

use App\User;
use App\Helpers\Qs;
use App\Http\Requests\UserRequest;
use App\Models\ClassType;
use App\Models\Lga;
use App\Models\MyClass;
use App\Models\Nationality;
use App\Models\Section;
use App\Models\State;
use App\Models\StudentEnrollment;
use App\Models\StudentRecord;
use App\Models\UserType;
use App\Repositories\MyClassRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegistrarAccessTest extends TestCase
{
    public function test_registrar_can_edit_staff_profile_without_staff_record()
    {
        $registrar = User::create([
            'name' => 'Registrar Without Staff Record',
            'username' => 'registrar-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($registrar->id) . '/edit')
            ->assertOk()
            ->assertSee('Date of Employment:');
    }

    public function test_teacher_class_roster_uses_the_shared_class_view()
    {
        $teacher = User::create([
            'name' => 'Class View Teacher',
            'username' => 'teacher-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'teacher',
            'code' => strtoupper(uniqid()),
        ]);
        $class = MyClass::create([
            'name' => 'Shared Class View ' . uniqid(),
            'class_type_id' => null,
        ]);
        $section = Section::create([
            'name' => 'A',
            'my_class_id' => $class->id,
            'active' => 1,
        ]);
        $student = User::create([
            'name' => 'Shared View Student',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);
        StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'session' => Qs::getCurrentSession(),
            'grad' => 0,
        ]);

        $this->actingAs($teacher)
            ->get('http://localhost/students/list/' . $class->id)
            ->assertOk()
            ->assertSee($class->name)
            ->assertSee('Students')
            ->assertSee($student->name)
            ->assertDontSee('All ' . $class->name . ' Students');
    }

    public function test_class_section_collection_preserves_creation_order()
    {
        $class = MyClass::create(['name' => 'Ordered Sections ' . uniqid(), 'class_type_id' => null]);
        $firstSection = Section::create(['name' => 'Z Section', 'my_class_id' => $class->id, 'active' => 1]);
        $secondSection = Section::create(['name' => 'A Section', 'my_class_id' => $class->id, 'active' => 1]);

        $sections = app(MyClassRepo::class)->getClassSections($class->id);

        $this->assertSame([$firstSection->id, $secondSection->id], $sections->pluck('id')->all());
    }

    public function test_registrar_can_change_user_and_student_usernames_from_their_profiles()
    {
        $registrar = User::create([
            'name' => 'Username Registrar',
            'username' => 'registrar-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);
        $teacher = User::create([
            'name' => 'Username Teacher',
            'username' => 'teacher-before-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'teacher',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users/' . Qs::hash($teacher->id) . '/edit')
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertDontSee('disabled type="text" name="username"', false);

        $teacherUsername = 'teacher-after-' . uniqid();
        $this->actingAs($registrar)
            ->put('http://localhost/users/' . Qs::hash($teacher->id), [
                'name' => $teacher->name,
                'username' => $teacherUsername,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'username' => $teacherUsername]);

        $stateId = DB::table('states')->insertGetId(['name' => 'Username Test State']);
        $lgaId = DB::table('lgas')->insertGetId(['state_id' => $stateId, 'name' => 'Username Test LGA']);
        $nationalityId = DB::table('nationalities')->insertGetId(['name' => 'Username Test Nationality']);
        $class = MyClass::create(['name' => 'Username Test Class', 'class_type_id' => null]);
        $section = Section::create(['name' => 'A', 'my_class_id' => $class->id, 'active' => 1]);
        $student = User::create([
            'name' => 'Username Student',
            'username' => 'student-before-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
            'address' => '123 Example Street',
            'gender' => 'Female',
        ]);
        $record = StudentRecord::create([
            'user_id' => $student->id,
            'my_class_id' => $class->id,
            'section_id' => $section->id,
            'session' => Qs::getCurrentSession(),
            'grad' => 0,
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/students/' . Qs::hash($record->id) . '/edit')
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee($student->username);

        $studentUsername = 'student-after-' . uniqid();
        $this->actingAs($registrar)
            ->put('http://localhost/students/' . Qs::hash($record->id), [
                'name' => $student->name,
                'username' => $studentUsername,
                'address' => $student->address,
                'gender' => $student->gender,
                'email' => '',
                'my_class_id' => $class->id,
                'section_id' => $section->id,
                'state_id' => $stateId,
                'lga_id' => $lgaId,
                'nal_id' => $nationalityId,
                'my_parent_id' => '',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'username' => $studentUsername]);
    }

    public function test_parent_creation_does_not_require_hidden_location_fields()
    {
        $parentTypeId = UserType::where('title', 'parent')->value('id');
        $request = UserRequest::create('/users', 'POST', [
            'name' => 'Parent Account',
            'user_type' => Qs::hash($parentTypeId),
            'address' => '123 Example Street',
        ]);

        $rules = $request->rules();

        foreach(['state_id', 'lga_id', 'nal_id'] as $field) {
            $this->assertStringContainsString('nullable', $rules[$field]);
            $this->assertStringNotContainsString('required', $rules[$field]);
        }
    }

    public function test_registrar_can_open_parent_edit_page_without_student_enrollment_data()
    {
        $registrar = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $parent = User::create([
            'name' => 'Parent Account',
            'username' => 'parent-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'parent',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users/'.Qs::hash($parent->id).'/edit')
            ->assertOk()
            ->assertSee('Relationship to Children')
            ->assertSee('Children');
    }

    public function test_registrar_account_password_must_be_between_8_and_30_characters()
    {
        $request = UserRequest::create('/users', 'POST', [
            'name' => 'Long Password Registrar',
            'username' => 'registrar_long_' . uniqid(),
            'password' => str_repeat('x', 30),
            'user_type' => UserType::where('title', 'registrar')->value('id'),
            'address' => '123 Example Street',
            'state_id' => 1,
            'lga_id' => 1,
            'nal_id' => 1,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));

        $request->merge(['password' => str_repeat('x', 31)]);
        $longPasswordValidator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($longPasswordValidator->passes());
        $this->assertArrayHasKey('password', $longPasswordValidator->errors()->toArray());

        $request->merge(['password' => str_repeat('x', 7)]);
        $shortPasswordValidator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($shortPasswordValidator->passes());
        $this->assertArrayHasKey('password', $shortPasswordValidator->errors()->toArray());
    }

    public function test_registrar_can_manage_users_and_enrollment_while_administrator_cannot()
    {
        $registrar = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $administrator = User::create([
            'name' => 'School Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'administrator',
            'code' => strtoupper(uniqid()),
        ]);

        $legacyAdmin = User::create([
            'name' => 'School Admin',
            'username' => 'admin-' . uniqid(),
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users')
            ->assertOk()
            ->assertSee('Registrars');

        $this->actingAs($registrar)
            ->get('http://localhost/users/create?type=registrar')
            ->assertOk()
            ->assertSee('Registrar Account Requirements');

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students/inbox')
            ->assertOk()
            ->assertSee('Pending Forms');

        $this->actingAs($registrar)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Students');

        $this->actingAs($administrator)
            ->get('http://localhost/users')
            ->assertOk()
            ->assertSee('Manage Users')
            ->assertDontSee('Create New User');

        $this->actingAs($administrator)
            ->get('http://localhost/users/' . \App\Helpers\Qs::hash($registrar->id) . '/edit')
            ->assertRedirect(route('login'));

        $this->actingAs($administrator)
            ->get('http://localhost/users/create?type=registrar')
            ->assertRedirect(route('login'));

        $this->actingAs($administrator)
            ->get('http://localhost/registrar/students/inbox')
            ->assertRedirect(route('login'));

        $this->actingAs($legacyAdmin)
            ->get('http://localhost/users')
            ->assertRedirect(route('login'));

        $this->actingAs($legacyAdmin)
            ->get('http://localhost/registrar/students/inbox')
            ->assertRedirect(route('login'));

        $this->actingAs($administrator)
            ->get('http://localhost/super_admin/students/inbox')
            ->assertNotFound();
    }

    public function test_registrar_can_access_dashboard_and_edit_classes()
    {
        $registrar = User::create([
            'name' => 'Classes Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $administrator = User::create([
            'name' => 'Statistics Administrator',
            'username' => 'administrator-' . uniqid(),
            'email' => 'administrator-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'administrator',
            'code' => strtoupper(uniqid()),
        ]);

        $class = MyClass::create([
            'name' => 'Registrar Test Class ' . uniqid(),
            'code' => strtoupper(uniqid()),
            'class_type_id' => ClassType::query()->value('id'),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertDontSee('Total Students')
            ->assertDontSee('id="page-header"', false)
            ->assertSee('Classes')
            ->assertSee($class->name);

        $this->actingAs($registrar)
            ->get('http://localhost/super_admin/school-statistics/students')
            ->assertOk()
            ->assertSee('School Statistics Full Lists');

        $this->actingAs($registrar)
            ->get('http://localhost/super_admin/classes-students/' . $class->id)
            ->assertOk()
            ->assertSee($class->name);

        $this->actingAs($registrar)
            ->get('http://localhost/classes/' . $class->id . '/edit')
            ->assertOk();

        $updatedClassName = 'Updated Registrar Class ' . uniqid();
        $this->actingAs($registrar)
            ->put('http://localhost/classes/' . $class->id, [
                'name' => $updatedClassName,
                'code' => $class->code,
            ])
            ->assertRedirect(route('classes.index'));

        $this->assertDatabaseHas('my_classes', ['id' => $class->id, 'name' => $updatedClassName]);

        $this->actingAs($administrator)
            ->get('http://localhost/super_admin/school-statistics/students')
            ->assertOk();

        $this->actingAs($administrator)
            ->get('http://localhost/super_admin/classes-students/' . $class->id)
            ->assertOk();

        $this->actingAs($administrator)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertSee('Classes');
    }

    public function test_registrar_student_directory_is_alphabetical_and_allows_student_edits()
    {
        $registrar = User::create([
            'name' => 'Student List Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $class = MyClass::create([
            'name' => 'Directory Test Class ' . uniqid(),
            'code' => strtoupper(uniqid()),
            'class_type_id' => ClassType::query()->value('id'),
        ]);
        $section = Section::create(['name' => 'A', 'my_class_id' => $class->id, 'active' => 1]);
        $records = collect(['Zulu Student', 'Alpha Student'])->map(function ($name) use ($class, $section) {
            $student = User::create([
                'name' => $name,
                'username' => 'student-' . uniqid(),
                'password' => bcrypt('password'),
                'user_type' => 'student',
                'code' => strtoupper(uniqid()),
            ]);

            return StudentRecord::create([
                'user_id' => $student->id,
                'my_class_id' => $class->id,
                'section_id' => $section->id,
                'session' => Qs::getCurrentSession(),
                'grad' => 0,
            ]);
        });

        $totalStudents = User::where('user_type', 'student')->count();
        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students')
            ->assertOk()
            ->assertSee('data-list-filter="registrar-students-table"', false)
            ->assertSee('0 of '.$totalStudents.' entries selected')
            ->assertSee('Delete Selected')
            ->assertSeeInOrder(['Student, Alpha', 'Student, Zulu']);

        $this->actingAs($registrar)
            ->get('http://localhost/students/' . Qs::hash($records->last()->id) . '/edit')
            ->assertOk();
        $selectedStudentId = $records->first()->user_id;
        $this->actingAs($registrar)
            ->post('http://localhost/registrar/students/delete-users', [
                'user_ids' => [$selectedStudentId, $registrar->id],
            ])
            ->assertStatus(302);

        $this->assertDatabaseMissing('users', ['id' => $selectedStudentId]);
        $this->assertDatabaseHas('users', ['id' => $registrar->id, 'user_type' => 'registrar']);
    }


    public function test_student_with_enrollment_data_but_no_student_record_is_still_listed_and_editable()
    {
        $registrar = User::create([
            'name' => 'Enrollment Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $class = MyClass::with('section')->firstOrFail();
        $student = User::create([
            'name' => 'Enrollment Only Student',
            'username' => 'student-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'student',
            'code' => strtoupper(uniqid()),
        ]);

        StudentEnrollment::create([
            'surname' => 'Only',
            'first_name' => 'Enrollment',
            'dob' => '2014-05-10',
            'place_of_birth_town' => 'Town',
            'gender' => 'Female',
            'religion' => 'Christian',
            'home_town' => 'Town',
            'contact_number' => '09170000000',
            'last_school_name' => 'Test School',
            'last_school_type' => 'PRIVATE',
            'last_school_address' => 'Test Address',
            'grade_enrolling' => 'Grade 12',
            'my_class_id' => $class->id,
            'section_id' => optional($class->section->first())->id,
            'account_username' => $student->username,
            'password' => 'TEMPPASS',
            'status' => 'approved',
            'enrolled_student_user_id' => $student->id,
            'enrolled_at' => now(),
        ]);

        $this->assertDatabaseMissing('student_records', ['user_id' => $student->id]);

        $this->actingAs($registrar)
            ->get('http://localhost/registrar/students')
            ->assertOk()
            ->assertSee('Student, Enrollment Only')
            ->assertSee($class->name)
            ->assertSee('Edit Student, Enrollment Only')
            ->assertDontSee('No student record');
    }

    public function test_administrator_user_table_hides_serial_phone_and_email_columns()
    {
        $registrar = User::create([
            'name' => 'User List Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $response = $this->actingAs($registrar)->get('http://localhost/users')->assertOk();
        foreach (['administrator', 'registrar', 'accountant', 'teacher', 'parent'] as $type) {
            $this->assertStringContainsString('data-list-filter="' . $type . '-users-table"', $response->getContent());
        }
        preg_match('/<table id="administrator-users-table".*?<\/table>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('<th>S/N</th>', $matches[0]);
        $this->assertStringNotContainsString('<th>Phone</th>', $matches[0]);
        $this->assertStringNotContainsString('<th>Email</th>', $matches[0]);
        $this->assertStringNotContainsString('<th class="action-column">Action</th>', $matches[0]);
        $this->assertStringNotContainsString('action-cell action-column', $matches[0]);
    }

    public function test_all_user_type_lists_have_no_action_column()
    {
        $registrar = User::create([
            'name' => 'Table Check Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $response = $this->actingAs($registrar)->get('http://localhost/users')->assertOk();
        preg_match_all('/class="[^"]*\bselection-counter\b[^"]*"[^>]*>0 of \d+ entries selected/', $response->getContent(), $counterMatches);
        $this->assertCount(5, $counterMatches[0]);

        foreach (['administrator', 'registrar', 'accountant', 'teacher', 'parent'] as $type) {
            $tableStart = strpos($response->getContent(), '<table id="' . $type . '-users-table"');
            $this->assertNotFalse($tableStart, 'Missing '.$type.' user table.');
            $tableEnd = strpos($response->getContent(), '</table>', $tableStart);
            $table = substr($response->getContent(), $tableStart, $tableEnd - $tableStart + strlen('</table>'));
            $this->assertStringNotContainsString('<th>S/N</th>', $table);
            $this->assertStringNotContainsString('<th>Phone</th>', $table);
            $this->assertStringNotContainsString('<th>Email</th>', $table);
            $this->assertStringNotContainsString('<th class="action-column">Action</th>', $table);
            $this->assertStringNotContainsString('action-cell action-column', $table);
            $this->assertStringNotContainsString('<th class="photo-column">Photo</th>', $table);
            if($type === 'accountant') {
                $this->assertStringContainsString('class="select-column"', $table);
                $this->assertStringContainsString('class="bulk-select-checkbox"', $table);
            }
        }

        $this->assertStringContainsString('Grades of Child/Children', $table);
    }
}