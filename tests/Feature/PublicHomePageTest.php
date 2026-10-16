<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PublicHomePageTest extends TestCase
{
    public function test_public_home_page_is_accessible_without_login()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('ENROLL NOW');
    }

    public function test_login_page_has_an_accessible_password_visibility_toggle()
    {
        $this->get('http://localhost/login')
            ->assertOk()
            ->assertSee('id="login-password"', false)
            ->assertSee('id="toggle-login-password"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('icon-eye-blocked', false)
            ->assertSee('passwordInput.type = showPassword ? \'text\' : \'password\';', false);
    }

    public function test_administrator_can_upload_login_page_background_from_home_page_editor()
    {
        $administrator = User::create([
            'name' => 'Home Page Administrator',
            'username' => 'home-page-admin-' . uniqid(),
            'password' => bcrypt('password'),
            'user_type' => 'administrator',
            'code' => 'ADMIN' . uniqid(),
        ]);

        View::share('errors', new ViewErrorBag());
        $this->actingAs($administrator)
            ->get('http://localhost/edit-home-page')
            ->assertOk()
            ->assertSee('Login Page')
            ->assertSee('home_page_login_background', false);

        Storage::fake('public');
        $this->actingAs($administrator)
            ->put('http://localhost/edit-home-page', [
                'home_page_login_background' => UploadedFile::fake()->create('login-background.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect();

        $background = Setting::where('type', 'home_page_login_background')->firstOrFail();
        $this->assertStringContainsString('storage/uploads/home/', $background->description);
        Storage::disk('public')->assertExists(str_replace(asset('storage/'), '', $background->description));

        Auth::logout();
        $this->get('http://localhost/login')
            ->assertOk()
            ->assertSee('class="page-content login-cover" style="background-image: linear-gradient', false)
            ->assertSee($background->description, false);
    }
}
