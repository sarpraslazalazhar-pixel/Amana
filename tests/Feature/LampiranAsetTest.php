<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Kategori;
use App\Models\LampiranAset;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LampiranAsetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewer;

    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->viewer = User::create([
            'name' => 'Viewer',
            'email' => 'viewer@test.com',
            'password' => bcrypt('password'),
            'role' => 'viewer',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Dell']);
        $lokasi = Lokasi::create(['nama_lokasi' => 'Gudang IT', 'kode_lokasi' => 'GD01']);
        $pj = PenanggungJawab::create(['nama' => 'Budi Santoso', 'jabatan' => 'Koordinator']);

        $this->aset = Aset::create([
            'nama_aset' => 'Server Cadangan',
            'kode_aset' => 'ELK-2026-GD01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 166666,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    // ========================================
    // UPLOAD TESTS
    // ========================================

    public function test_super_admin_can_upload_lampiran(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->create('invoice.pdf', 1024, 'application/pdf'),
            'lampiran_label' => 'Invoice pembelian',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lampiran_aset', [
            'aset_id' => $this->aset->id,
            'file_name' => 'invoice.pdf',
            'label' => 'Invoice pembelian',
            'uploaded_by' => $this->admin->id,
        ]);

        $lampiran = LampiranAset::first();
        Storage::disk('public')->assertExists($lampiran->file_path);
    }

    public function test_upload_rejects_file_over_5mb(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('lampiran_file');
        $this->assertDatabaseCount('lampiran_aset', 0);
    }

    public function test_upload_rejects_invalid_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ]);

        $response->assertSessionHasErrors('lampiran_file');
        $this->assertDatabaseCount('lampiran_aset', 0);
    }

    public function test_upload_rejects_when_max_limit_reached(): void
    {
        // Create 5 existing lampiran
        for ($i = 0; $i < 5; $i++) {
            LampiranAset::create([
                'aset_id' => $this->aset->id,
                'file_path' => "lampiran_aset/file_{$i}.pdf",
                'file_name' => "file_{$i}.pdf",
                'file_size' => 1024,
                'file_type' => 'application/pdf',
                'uploaded_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('lampiran_file');
        $this->assertDatabaseCount('lampiran_aset', 5);
    }

    public function test_upload_without_label_is_valid(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->image('foto_nota.jpg', 800, 600),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lampiran_aset', [
            'aset_id' => $this->aset->id,
            'label' => null,
        ]);
    }

    // ========================================
    // DOWNLOAD TESTS
    // ========================================

    public function test_user_can_download_lampiran(): void
    {
        Storage::disk('public')->put('lampiran_aset/test.pdf', 'fake pdf content');

        $lampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'file_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->viewer)->get(route('aset.lampiran.download', $lampiran->id));

        $response->assertOk();
        $response->assertDownload('test.pdf');
    }

    // ========================================
    // DELETE TESTS
    // ========================================

    public function test_super_admin_can_delete_lampiran(): void
    {
        Storage::disk('public')->put('lampiran_aset/to_delete.pdf', 'content');

        $lampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/to_delete.pdf',
            'file_name' => 'to_delete.pdf',
            'file_size' => 512,
            'file_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('aset.lampiran.destroy', $lampiran->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('lampiran_aset', 0);
        Storage::disk('public')->assertMissing('lampiran_aset/to_delete.pdf');
    }

    public function test_viewer_cannot_delete_lampiran(): void
    {
        $lampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/protected.pdf',
            'file_name' => 'protected.pdf',
            'file_size' => 512,
            'file_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->viewer)->delete(route('aset.lampiran.destroy', $lampiran->id));

        $response->assertForbidden();
        $this->assertDatabaseCount('lampiran_aset', 1);
    }

    // ========================================
    // MODEL ACCESSOR TESTS
    // ========================================

    public function test_formatted_size_accessor(): void
    {
        $lampiran = new LampiranAset(['file_size' => 2048]);
        $this->assertEquals('2 KB', $lampiran->formatted_size);

        $lampiran = new LampiranAset(['file_size' => 1572864]);
        $this->assertEquals('1.5 MB', $lampiran->formatted_size);

        $lampiran = new LampiranAset(['file_size' => 500]);
        $this->assertEquals('500 B', $lampiran->formatted_size);
    }

    public function test_icon_class_accessor(): void
    {
        $pdf = new LampiranAset(['file_type' => 'application/pdf']);
        $this->assertEquals('ti-file-type-pdf', $pdf->icon_class);

        $img = new LampiranAset(['file_type' => 'image/jpeg']);
        $this->assertEquals('ti-photo', $img->icon_class);

        $doc = new LampiranAset(['file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        $this->assertEquals('ti-file-type-doc', $doc->icon_class);

        $xls = new LampiranAset(['file_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        $this->assertEquals('ti-file-type-xls', $xls->icon_class);
    }
}
