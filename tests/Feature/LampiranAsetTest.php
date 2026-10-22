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
        $errors = session('errors')->get('lampiran_file');
        $this->assertStringContainsString('kebesaran', strtolower($errors[0]));
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
            'file_name' => 'foto_nota.webp',
            'file_type' => 'image/webp',
        ]);

        $lampiran = LampiranAset::first();
        $this->assertStringEndsWith('.webp', $lampiran->file_path);
        Storage::disk('public')->assertExists($lampiran->file_path);
    }

    public function test_upload_gambar_lampiran_png_dikonversi_ke_format_webp(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->image('struk_belanja.png', 1000, 800),
            'lampiran_label' => 'Bukti Struk',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lampiran = LampiranAset::where('label', 'Bukti Struk')->first();
        $this->assertNotNull($lampiran);
        $this->assertEquals('struk_belanja.webp', $lampiran->file_name);
        $this->assertEquals('image/webp', $lampiran->file_type);
        $this->assertStringEndsWith('.webp', $lampiran->file_path);
        Storage::disk('public')->assertExists($lampiran->file_path);
    }

    public function test_upload_dokumen_non_gambar_tetap_disimpan_asli_tanpa_konversi(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.lampiran.store', $this->aset->id), [
            'lampiran_file' => UploadedFile::fake()->create('manual_book.pdf', 500, 'application/pdf'),
            'lampiran_label' => 'Buku Manual',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lampiran = LampiranAset::where('label', 'Buku Manual')->first();
        $this->assertNotNull($lampiran);
        $this->assertEquals('manual_book.pdf', $lampiran->file_name);
        $this->assertEquals('application/pdf', $lampiran->file_type);
        $this->assertStringEndsWith('.pdf', $lampiran->file_path);
        Storage::disk('public')->assertExists($lampiran->file_path);
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

    public function test_file_url_accessor(): void
    {
        $lampiran = new LampiranAset(['file_path' => 'lampiran_aset/sample.pdf']);
        $this->assertEquals(asset('storage/lampiran_aset/sample.pdf'), $lampiran->file_url);

        $empty = new LampiranAset(['file_path' => null]);
        $this->assertNull($empty->file_url);
    }

    public function test_show_page_renders_lihat_buttons_for_lampiran_and_keuangan(): void
    {
        $imageLampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/foto.webp',
            'file_name' => 'foto.webp',
            'file_size' => 1024,
            'file_type' => 'image/webp',
            'label' => 'Foto Fisik',
            'uploaded_by' => $this->admin->id,
        ]);

        $pdfLampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/dokumen.pdf',
            'file_name' => 'dokumen.pdf',
            'file_size' => 2048,
            'file_type' => 'application/pdf',
            'label' => 'Buku Manual',
            'uploaded_by' => $this->admin->id,
        ]);

        $keu = \App\Models\KeuanganAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-10-21',
            'nominal' => 250000,
            'jenis_transaksi' => "Servis AC 1/2 PK 'Daikin'",
            'keterangan' => 'Ganti filter',
            'lampiran' => 'keuangan/nota_servis.jpg',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $this->aset->id));

        $response->assertOk();
        $response->assertSee('Foto Fisik');
        $response->assertSee('Buku Manual');
        $response->assertSee("Servis AC 1/2 PK &#039;Daikin&#039;", false);
        $response->assertSee('openImageModal(' . \Illuminate\Support\Js::from($imageLampiran->file_url), false);
        $response->assertSee('openImageModal(' . \Illuminate\Support\Js::from($keu->lampiran_url), false);
        $response->assertSee(e($pdfLampiran->file_url), false);
    }

    public function test_is_image_and_is_pdf_fallback_to_extension_when_file_type_is_null_or_empty(): void
    {
        $imgNullMime = new LampiranAset([
            'file_path' => 'lampiran_aset/sample.webp',
            'file_name' => 'sample.webp',
            'file_type' => null,
        ]);
        $this->assertTrue($imgNullMime->is_image);
        $this->assertFalse($imgNullMime->is_pdf);

        $imgExtOnly = new LampiranAset([
            'file_path' => 'lampiran_aset/hash123',
            'file_name' => 'FOTO_BARANG.JPG',
            'file_type' => 'jpg',
        ]);
        $this->assertTrue($imgExtOnly->is_image);

        $pdfNullMime = new LampiranAset([
            'file_path' => 'lampiran_aset/manual.PDF',
            'file_name' => 'manual.pdf',
            'file_type' => null,
        ]);
        $this->assertTrue($pdfNullMime->is_pdf);
        $this->assertFalse($pdfNullMime->is_image);

        $docFile = new LampiranAset([
            'file_path' => 'lampiran_aset/berkas.docx',
            'file_name' => 'berkas.docx',
            'file_type' => null,
        ]);
        $this->assertFalse($docFile->is_image);
        $this->assertFalse($docFile->is_pdf);
        $this->assertEquals('ti-file-type-doc', $docFile->icon_class);
    }

    public function test_show_page_renders_lihat_button_for_generic_document(): void
    {
        $docLampiran = LampiranAset::create([
            'aset_id' => $this->aset->id,
            'file_path' => 'lampiran_aset/kontrak.docx',
            'file_name' => 'kontrak.docx',
            'file_size' => 4096,
            'file_type' => 'application/octet-stream',
            'label' => 'Kontrak Pembelian',
            'uploaded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $this->aset->id));
        $response->assertOk();
        $response->assertSee('Kontrak Pembelian');
        $response->assertSee(e($docLampiran->file_url), false);
        $response->assertSee('Buka Dokumen di Tab Baru');
    }
}
