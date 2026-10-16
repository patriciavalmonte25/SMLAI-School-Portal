<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountantBillingAccessTest extends TestCase
{
    public function test_registrars_can_create_and_manage_accountant_accounts()
    {
        $registrar = User::create([
            'name' => 'School Registrar',
            'username' => 'registrar-' . uniqid(),
            'email' => 'registrar-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'registrar',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($registrar)
            ->get('http://localhost/users/create?type=accountant')
            ->assertOk()
            ->assertSee('Accountant Account Requirements');

        $this->actingAs($registrar)
            ->get('http://localhost/users')
            ->assertOk()
            ->assertSee('Accountants');
    }

    public function test_accountants_can_access_billings_and_administrators_cannot()
    {
        $accountant = User::create([
            'name' => 'Billing Accountant',
            'username' => 'accountant-' . uniqid(),
            'email' => 'accountant-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'accountant',
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

        $this->actingAs($accountant)
            ->get('http://localhost/accountant/billings')
            ->assertOk();

        $this->actingAs($accountant)
            ->get('http://localhost/accountant/billings/list/1?edit=1')
            ->assertOk()
            ->assertSee('Save Progress')
            ->assertSee('Remove Row')
            ->assertSee('Remove Column')
            ->assertSee('Undo')
            ->assertSee('Redo');

        $saveResponse = $this->actingAs($accountant)
            ->post('http://localhost/accountant/billings/list/1/save', [
                'layout' => [
                    'columns' => [
                        ['key' => 'fee', 'title' => 'Fee'],
                        ['key' => 'amount', 'title' => 'Amount'],
                    ],
                    'rows' => [
                        ['id' => 'tuition', 'cells' => ['fee' => 'Tuition', 'amount' => '']],
                        ['id' => 'total', 'cells' => ['fee' => 'Total', 'amount' => '']],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $lastSaved = Carbon::parse($saveResponse->json('updated_at'))->format('d M Y h:i:s A');
        $overviewResponse = $this->actingAs($accountant)
            ->get('http://localhost/accountant/billings')
            ->assertOk()
            ->assertSee($lastSaved);
        $this->assertStringContainsString('no-store', $overviewResponse->headers->get('Cache-Control'));

        $this->actingAs($administrator)
            ->get('http://localhost/accountant/billings')
            ->assertRedirect(route('login'));

        $this->actingAs($administrator)
            ->get('http://localhost/accountant/billings/list/1')
            ->assertRedirect(route('login'));

        $this->actingAs($administrator)
            ->post('http://localhost/accountant/billings/list/1/save', [])
            ->assertRedirect(route('login'));
    }
}