<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsetExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@alazhar.org',
        ]);
    }

    public function test_can_view_central_export_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.export.index'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Ekspor Data Aset');
        $response->assertSee('Parameter & Filter Ekspor', false);
    }

    public function test_download_xlsx_export_file(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first();
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $lokasi = Lokasi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);
        $divisi = Divisi::where('kode_divisi', '2')->first();

        Aset::create([
            'nama_aset' => 'Laptop Lenovo Thinkpad T480',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202001',
            'kode_aset_lama' => 'OLD-1234',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'tanggal_pembelian' => '2020-01-15',
            'toko_distributor' => 'Distributor A',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.export.download', [
            'klasifikasi' => 'tetap',
            'kategori_id' => $kategori->id,
        ]));

        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Daftar_Aset_tetap_', $response->headers->get('Content-Disposition'));
    }

    public function test_export_tetap_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.tetap.export'));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_export_kelolaan_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.kelolaan.export'));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }
}
