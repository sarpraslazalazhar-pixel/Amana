<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\JurnalAset;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JurnalLampiranImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Asus']);
        $lokasi = Lokasi::create(['nama_lokasi' => 'Ruang IT', 'kode_lokasi' => 'IT01']);
        $pj = PenanggungJawab::create(['nama' => 'Budi Santoso', 'jabatan' => 'Staff IT']);

        $this->aset = Aset::create([
            'nama_aset' => 'Laptop ROG',
            'kode_aset' => 'ELK-2026-IT01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'harga_total' => 15000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 250000,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_jurnal_lampiran_accessors_and_helpers(): void
    {
        $jurnalImage = JurnalAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-09-01',
            'kejadian' => 'Layar LCD pecah',
            'lampiran' => 'jurnal_lampiran/foto_kerusakan.png',
            'user_id' => $this->admin->id,
        ]);

        $this->assertTrue($jurnalImage->is_image);
        $this->assertFalse($jurnalImage->is_pdf);
        $this->assertEquals('foto_kerusakan.png', $jurnalImage->lampiran_file_name);
        $this->assertEquals('ti-photo', $jurnalImage->icon_class);
        $this->assertStringContainsString('storage/jurnal_lampiran/foto_kerusakan.png', $jurnalImage->lampiran_url);

        $jurnalPdf = JurnalAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-09-02',
            'kejadian' => 'Nota service',
            'lampiran' => 'jurnal_lampiran/nota.pdf',
            'user_id' => $this->admin->id,
        ]);

        $this->assertFalse($jurnalPdf->is_image);
        $this->assertTrue($jurnalPdf->is_pdf);
        $this->assertEquals('nota.pdf', $jurnalPdf->lampiran_file_name);
        $this->assertEquals('ti-file-type-pdf', $jurnalPdf->icon_class);
    }

    public function test_upload_jurnal_dengan_gambar_dan_tampilkan_di_halaman_show(): void
    {
        $file = UploadedFile::fake()->image('bukti_perbaikan.jpg', 600, 400);

        $response = $this->actingAs($this->admin)->post(route('aset.jurnal.store', $this->aset->id), [
            'tanggal' => '2026-09-03',
            'kejadian' => 'Penggantian baterai laptop selesai',
            'lampiran' => $file,
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $jurnal = JurnalAset::where('aset_id', $this->aset->id)->first();
        $this->assertNotNull($jurnal);
        $this->assertNotNull($jurnal->lampiran);
        $this->assertTrue($jurnal->is_image);

        // Halaman detail aset menampilkan komponen thumbnail dan URL lampiran
        $showResponse = $this->actingAs($this->admin)->get(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'jurnal']));
        $showResponse->assertOk();
        $showResponse->assertSee('Foto / Gambar Bukti');
        $showResponse->assertSee($jurnal->lampiran_url);
    }

    public function test_storage_fallback_route_streams_image_safely(): void
    {
        Storage::disk('public')->put('jurnal_lampiran/test_image.png', 'fake-png-binary-data');

        $response = $this->get('/storage/jurnal_lampiran/test_image.png');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Content-Disposition', 'inline; filename="test_image.png"');
        $this->assertEquals('fake-png-binary-data', $response->getContent());
    }
}
