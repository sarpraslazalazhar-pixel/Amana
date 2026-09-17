<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin Al Azhar Peduli',
            'email' => 'admin@alazharpeduli.or.id',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->viewer = User::create([
            'name' => 'Auditor User',
            'email' => 'viewer@alazhar.or.id',
            'password' => Hash::make('password123'),
            'role' => 'viewer',
            'is_active' => true,
        ]);
    }

    public function test_login_page_renders_without_demo_shortcuts(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Akses Cepat Demo');
        $response->assertDontSee('login-admin');
        $response->assertDontSee('login-viewer');
        $response->assertDontSee('superadmin@alazhar.or.id');
        $response->assertSee('Masuk ke Dashboard');
    }

    public function test_demo_shortcut_routes_return_404(): void
    {
        $this->get('/login-admin')->assertStatus(404);
        $this->get('/login-viewer')->assertStatus(404);
    }

    public function test_super_admin_can_login_with_new_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@alazharpeduli.or.id',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@alazharpeduli.or.id',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_profile_routes_require_authentication(): void
    {
        $this->get(route('profile.index'))->assertRedirect(route('login'));
        $this->put(route('profile.update'))->assertRedirect(route('login'));
        $this->put(route('profile.password'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('profile.index'));

        $response->assertStatus(200);
        $response->assertSee('admin@alazharpeduli.or.id');
        $response->assertSee('Super Admin Al Azhar Peduli');
        $response->assertSee('Perbarui Kata Sandi');
    }

    public function test_authenticated_user_can_update_profile_info(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.update'), [
            'name' => 'Admin Utama AMANA',
            'email' => 'admin.utama@alazharpeduli.or.id',
        ]);

        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertSame('Admin Utama AMANA', $this->admin->name);
        $this->assertSame('admin.utama@alazharpeduli.or.id', $this->admin->email);
    }

    public function test_user_cannot_update_email_to_another_users_email(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.update'), [
            'name' => 'Admin Utama',
            'email' => $this->viewer->email,
        ]);

        $response->assertSessionHasErrors('email');
        $this->admin->refresh();
        $this->assertSame('admin@alazharpeduli.or.id', $this->admin->email);
    }

    public function test_authenticated_user_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'admin123',
            'password' => 'barubanget456',
            'password_confirmation' => 'barubanget456',
        ]);

        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('barubanget456', $this->admin->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'salah123',
            'password' => 'barubanget456',
            'password_confirmation' => 'barubanget456',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->admin->refresh();
        $this->assertFalse(Hash::check('barubanget456', $this->admin->password));
        $this->assertTrue(Hash::check('admin123', $this->admin->password));
    }
}
