<?php

namespace Tests\Feature;

use App\Models\PenanggungJawab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Super Admin Utama',
            'email' => 'admin@alazhar.or.id',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Viewer Standar',
            'email' => 'viewer@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('sistem.users.index'));

        $response->assertOk();
        $response->assertViewIs('sistem.users.index');
        $response->assertSee('Super Admin Utama');
        $response->assertSee('Viewer Standar');
    }

    public function test_viewer_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('sistem.users.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('sistem.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_filter_users_by_role_and_status(): void
    {
        $adminDua = User::factory()->create([
            'name' => 'Admin Tambahan Dua',
            'email' => 'admindua@alazhar.or.id',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $inactiveUser = User::factory()->create([
            'name' => 'Akun Nonaktif Khusus',
            'email' => 'nonaktif@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => false,
        ]);

        // Filter role super_admin (harus menampilkan Admin Tambahan Dua, tidak menampilkan Viewer Standar)
        $resRole = $this->actingAs($this->admin)->get(route('sistem.users.index', ['role' => 'super_admin']));
        $resRole->assertOk();
        $resRole->assertSee('Admin Tambahan Dua');
        $resRole->assertDontSee('Viewer Standar');

        // Filter status non_aktif (harus menampilkan Akun Nonaktif Khusus, tidak menampilkan Viewer Standar)
        $resStatus = $this->actingAs($this->admin)->get(route('sistem.users.index', ['status' => 'non_aktif']));
        $resStatus->assertOk();
        $resStatus->assertSee('Akun Nonaktif Khusus');
        $resStatus->assertDontSee('Viewer Standar');
    }

    public function test_super_admin_can_search_users_by_name_or_email(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Zulfa Khairunnisa',
            'email' => 'zulfa@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Bambang Sudarsono',
            'email' => 'bambang@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sistem.users.index', ['search' => 'zulfa@']));

        $response->assertOk();
        $response->assertSee('Zulfa Khairunnisa');
        $response->assertDontSee('Bambang Sudarsono');
    }

    public function test_super_admin_can_create_user_with_linked_penanggung_jawab(): void
    {
        $pic = PenanggungJawab::create([
            'nama' => 'Ustadz Ahmad Fauzi',
            'kode_pic' => '088',
            'jabatan' => 'Kepala Divisi',
            'status' => 'aktif',
        ]);

        $postData = [
            'name' => 'Ahmad Fauzi',
            'email' => 'fauzi@alazhar.or.id',
            'role' => 'super_admin',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active' => '1',
            'penanggung_jawab_id' => $pic->id,
        ];

        $response = $this->actingAs($this->admin)->post(route('sistem.users.store'), $postData);

        $response->assertRedirect(route('sistem.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Ahmad Fauzi',
            'email' => 'fauzi@alazhar.or.id',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $newUser = User::where('email', 'fauzi@alazhar.or.id')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('secret123', $newUser->password));

        // Cek penautan ke tabel penanggung_jawab
        $this->assertDatabaseHas('penanggung_jawab', [
            'id' => $pic->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_super_admin_can_create_user_without_linked_penanggung_jawab(): void
    {
        $postData = [
            'name' => 'Auditor Eksternal',
            'email' => 'auditor@eksternal.org',
            'role' => 'viewer',
            'password' => 'password2026',
            'password_confirmation' => 'password2026',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('sistem.users.store'), $postData);

        $response->assertRedirect(route('sistem.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'auditor@eksternal.org',
            'role' => 'viewer',
        ]);
    }

    public function test_validation_errors_when_creating_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('sistem.users.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'role' => 'super_admin',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_super_admin_can_update_user_details_and_penanggung_jawab(): void
    {
        $pic = PenanggungJawab::create([
            'nama' => 'Siti Nurhaliza',
            'kode_pic' => '099',
            'status' => 'aktif',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Nama Awal',
            'email' => 'awal@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'Nama Baru Terupdate',
            'email' => 'baru@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => '1',
            'penanggung_jawab_id' => $pic->id,
        ];

        $response = $this->actingAs($this->admin)->put(route('sistem.users.update', $targetUser->id), $updateData);

        $response->assertRedirect(route('sistem.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nama Baru Terupdate',
            'email' => 'baru@alazhar.or.id',
        ]);

        $this->assertDatabaseHas('penanggung_jawab', [
            'id' => $pic->id,
            'user_id' => $targetUser->id,
        ]);
    }

    public function test_super_admin_cannot_demote_self(): void
    {
        $response = $this->actingAs($this->admin)->put(route('sistem.users.update', $this->admin->id), [
            'name' => 'Super Admin Utama',
            'email' => 'admin@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->admin->refresh();
        $this->assertEquals('super_admin', $this->admin->role);
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->admin)->put(route('sistem.users.update', $this->admin->id), [
            'name' => 'Super Admin Utama',
            'email' => 'admin@alazhar.or.id',
            'role' => 'super_admin',
            'is_active' => '0',
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
    }

    public function test_super_admin_cannot_demote_last_active_super_admin(): void
    {
        // $this->admin is the only super admin
        $response = $this->actingAs($this->admin)->put(route('sistem.users.update', $this->admin->id), [
            'name' => 'Admin Baru',
            'email' => 'admin@alazhar.or.id',
            'role' => 'viewer',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Target Reset',
            'email' => 'target@alazhar.or.id',
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($this->admin)->post(route('sistem.users.reset-password', $targetUser->id), [
            'password' => 'newpassword2026',
            'password_confirmation' => 'newpassword2026',
        ]);

        $response->assertRedirect(route('sistem.users.index'));
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertTrue(Hash::check('newpassword2026', $targetUser->password));
    }

    public function test_super_admin_can_toggle_user_status(): void
    {
        $targetUser = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('sistem.users.toggle-status', $targetUser->id));

        $response->assertRedirect(route('sistem.users.index'));
        $targetUser->refresh();
        $this->assertFalse($targetUser->is_active);

        // Toggle back to true
        $this->actingAs($this->admin)->patch(route('sistem.users.toggle-status', $targetUser->id));
        $targetUser->refresh();
        $this->assertTrue($targetUser->is_active);
    }

    public function test_super_admin_cannot_toggle_own_status(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('sistem.users.toggle-status', $this->admin->id));

        $response->assertSessionHasErrors(['error']);
        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $pic = PenanggungJawab::create([
            'nama' => 'PIC Amil Terhubung',
            'kode_pic' => '077',
            'status' => 'aktif',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'User Mau Dihapus',
            'email' => 'hapus@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $pic->update(['user_id' => $targetUser->id]);

        $response = $this->actingAs($this->admin)->delete(route('sistem.users.destroy', $targetUser->id));

        $response->assertRedirect(route('sistem.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);

        // Pastikan relasi di PIC terlepas
        $pic->refresh();
        $this->assertNull($pic->user_id);
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('sistem.users.destroy', $this->admin->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_super_admin_cannot_delete_last_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('sistem.users.destroy', $this->admin->id));

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
