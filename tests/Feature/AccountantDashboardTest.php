<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AccountantDashboardTest extends TestCase
{
    public function test_accountant_dashboard_hides_statistics_and_keeps_calendar()
    {
        $accountant = User::create([
            'name' => 'Dashboard Accountant',
            'username' => 'accountant-' . uniqid(),
            'email' => 'accountant-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'accountant',
            'code' => strtoupper(uniqid()),
        ]);

        $this->actingAs($accountant)
            ->get('http://localhost/dashboard')
            ->assertOk()
            ->assertDontSee('Total Students')
            ->assertDontSee('Total Administrators')
            ->assertSee('School Events Calendar')
            ->assertDontSee('id="page-header"', false)
            ->assertDontSee('Administrative');

        $this->actingAs($accountant)
            ->get('http://localhost/accountant/billings')
            ->assertOk()
            ->assertDontSee('id="page-header"', false)
            ->assertDontSee('icon-plus-circle2', false);
    }
}