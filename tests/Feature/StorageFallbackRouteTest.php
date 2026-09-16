<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFallbackRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_public_can_access_stored_image_via_fallback_route(): void
    {
        Storage::disk('public')->put('aset_foto/sample_image.webp', 'binary-image-content');

        $response = $this->get('/storage/aset_foto/sample_image.webp');

        $response->assertStatus(200);
        $this->assertEquals('binary-image-content', $response->getContent());
        $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    public function test_non_existent_file_returns_404(): void
    {
        $response = $this->get('/storage/aset_foto/file_yang_tidak_ada.webp');

        $response->assertStatus(404);
    }

    public function test_directory_traversal_is_prevented(): void
    {
        $response = $this->get('/storage/../.env');
        $response->assertStatus(404);

        $response2 = $this->get('/storage/../../config/app.php');
        $response2->assertStatus(404);
    }

    public function test_admin_storage_link_requires_authentication(): void
    {
        $response = $this->get('/admin/storage-link');
        $response->assertRedirect('/login');
    }

    public function test_admin_storage_link_forbidden_for_non_super_admin(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($viewer)->get('/admin/storage-link');
        $response->assertStatus(403);
    }

    public function test_admin_storage_link_successful_for_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->get('/admin/storage-link');
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }
}
