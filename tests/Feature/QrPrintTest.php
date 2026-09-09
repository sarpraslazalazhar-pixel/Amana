<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kategori $kategori;

    private Merk $merk;

    private Lokasi $lokasi;

    private PenanggungJawab $pj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->kategori = Kategori::create([
            'nama_kategori' => 'Elektronik',
            'kode_kategori' => 'EL',
        ]);

        $this->merk = Merk::create([
            'nama_merk' => 'Asus',
        ]);

        $this->lokasi = Lokasi::create([
            'nama_lokasi' => 'Ruang IT',
            'kode_lokasi' => '101',
            'gedung' => 'Gedung Utama',
        ]);

        $this->pj = PenanggungJawab::create([
            'nama' => 'Budi Santoso',
            'kode_pic' => '050',
            'jabatan' => 'Staff IT',
        ]);
    }

    private function createSampleAset(string $kode = 'EL14D05021202601', string $nama = 'Laptop ROG'): Aset
    {
        return Aset::create([
            'nama_aset' => $nama,
            'kode_aset' => $kode,
            'kategori_id' => $this->kategori->id,
            'merk_id' => $this->merk->id,
            'tipe_model' => 'Zephyrus G14',
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-15',
            'toko_distributor' => 'Asus Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 416666.67,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_halaman_cetak_qr_dapat_diakses_oleh_user_login(): void
    {
        $this->actingAs($this->user);
        $this->createSampleAset();

        $response = $this->get(route('aset.qr.print'));
        $response->assertStatus(200);
        $response->assertSee('Cetak Label QR Code Massal');
        $response->assertSee('Kertas A4 (20 Stiker Kotak)');
        $response->assertSee('Laptop ROG');
    }

    public function test_halaman_cetak_qr_dengan_preselected_ids(): void
    {
        $this->actingAs($this->user);
        $aset1 = $this->createSampleAset('EL14D05021202601', 'Laptop ROG 1');
        $aset2 = $this->createSampleAset('EL14D05021202602', 'Laptop ROG 2');

        $response = $this->get(route('aset.qr.print', ['selected_ids' => "{$aset1->id},{$aset2->id}"]));
        $response->assertStatus(200);
        $response->assertSee((string) $aset1->id);
        $response->assertSee((string) $aset2->id);
    }

    public function test_preview_lembar_cetak_menghasilkan_lembaran_sesuai_template(): void
    {
        $this->actingAs($this->user);
        $aset1 = $this->createSampleAset('EL14D05021202601', 'Laptop Asus ROG');

        $response = $this->post(route('aset.qr.preview'), [
            'aset_ids' => [$aset1->id],
            'template_key' => 'a4_grid_24',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Preview Lembar Cetak Label QR');
        $response->assertSee('Laptop Asus ROG');
        $response->assertSee('EL14D05021202601');
    }

    public function test_download_pdf_menghasilkan_file_pdf(): void
    {
        $this->actingAs($this->user);
        $aset1 = $this->createSampleAset('EL14D05021202601', 'Laptop Asus ROG');

        $response = $this->post(route('aset.qr.download-pdf'), [
            'aset_ids' => [$aset1->id],
            'template_key' => 'a4_grid_24',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_print_direct_menghasilkan_halaman_cetak(): void
    {
        $this->actingAs($this->user);
        $aset1 = $this->createSampleAset('EL14D05021202601', 'Laptop Asus ROG');

        $response = $this->post(route('aset.qr.print-direct'), [
            'aset_ids' => [$aset1->id],
            'template_key' => 'thermal_single',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Cetak Label QR Code Aset AMANA');
        $response->assertSee('Laptop Asus ROG');
    }

    public function test_validasi_input_saat_aset_ids_kosong(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('aset.qr.preview'), [
            'aset_ids' => [],
            'template_key' => 'a4_grid_24',
        ]);

        $response->assertSessionHasErrors('aset_ids');
    }
}
