<?php

namespace Tests\Feature;

use App\Helpers\Qs;
use App\User;
use Tests\TestCase;

class AdministratorUserListReadOnlyTest extends TestCase
{
    public function test_administrator_can_view_users_and_profiles_but_cannot_edit_accounts()
    {
        $administrator = $this->makeUser('administrator', 'Portal Administrator');
        $teacher = $this->makeUser('teacher', 'Teacher Profile');
        $this->assertSame('administrator', $administrator->fresh()->user_type);

        $this->actingAs($administrator);
        $this->assertFalse(Qs::userIsRegistrar());
        $response = $this->get('http://localhost/users');
        $response->assertOk()
            ->assertSee('Users')
            ->assertSee(route('users.show', Qs::hash($teacher->id)), false);

        $content = $response->getContent();
        $this->assertFalse(strpos($content, 'Create New User') !== false, 'Administrator can see the create-user menu.');
        foreach (['administrators', 'registrars', 'accountants', 'teacher', 'parent'] as $tab) {
            $this->assertNotFalse(strpos($content, 'href="#ut-' . $tab . '"'), 'Missing administrator user tab: ' . $tab);
        }
        $this->assertFalse(strpos($content, route('users.edit', Qs::hash($teacher->id))) !== false, 'Administrator can see an edit link.');
        $this->assertFalse(strpos($content, '<th class="action-column">Action</th>') !== false, 'Administrator can see the Action column.');
        $deletePosition = strpos($content, 'Delete Selected');
        $this->assertFalse($deletePosition !== false, $deletePosition === false ? '' : strip_tags(substr($content, max(0, $deletePosition - 220), 520)));

        $classesPosition = strpos($content, 'Classes and Students');
        $usersPosition = strpos($content, '<span>Users</span>');
        $timetablesPosition = strpos($content, '<span>Timetables</span>');
        $this->assertLessThan($usersPosition, $classesPosition);
        $this->assertLessThan($timetablesPosition, $usersPosition);

        $this->get('http://localhost/users/' . Qs::hash($teacher->id))
            ->assertOk()
            ->assertSee($teacher->name);
        $this->get('http://localhost/users/' . Qs::hash($teacher->id) . '/edit')
            ->assertStatus(302);
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
