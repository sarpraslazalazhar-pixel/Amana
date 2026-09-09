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

class MasterLokasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_halaman_master_lokasi_dapat_diakses_oleh_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('data.lokasi.index'));

        $response->assertOk()
            ->assertSeeText('Data Master Lokasi')
            ->assertSeeText('Peta Sebaran Lokasi')
            ->assertSeeText('Kantor Cirendeu')
            ->assertSee('overview-map');
    }

    public function test_super_admin_dapat_menambahkan_lokasi_baru_dengan_koordinat(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.lokasi.store'), [
            'kode_lokasi' => '700',
            'nama_lokasi' => 'KPW Bali (Denpasar)',
            'gedung' => 'Kantor Perwakilan Wilayah (KPW)',
            'alamat_lengkap' => 'Jl. Diponegoro No. 10, Denpasar, Bali',
            'latitude' => -8.6705,
            'longitude' => 115.2126,
        ]);

        $response->assertRedirect(route('data.lokasi.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lokasi', [
            'kode_lokasi' => '700',
            'nama_lokasi' => 'KPW Bali (Denpasar)',
            'latitude' => -8.6705,
            'longitude' => 115.2126,
        ]);
    }

    public function test_super_admin_dapat_mengubah_data_lokasi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $lokasi = Lokasi::where('kode_lokasi', '100')->first();

        $response = $this->actingAs($admin)->put(route('data.lokasi.update', $lokasi->id), [
            'kode_lokasi' => '100',
            'nama_lokasi' => 'Kantor Pusat Cirendeu (Renovasi)',
            'gedung' => 'Kantor Cirendeu',
            'alamat_lengkap' => 'Jl. Cirendeu Raya No. 1 Gedung Baru',
            'latitude' => -6.3094,
            'longitude' => 106.7726,
        ]);

        $response->assertRedirect(route('data.lokasi.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lokasi', [
            'id' => $lokasi->id,
            'nama_lokasi' => 'Kantor Pusat Cirendeu (Renovasi)',
        ]);
    }

    public function test_lokasi_dapat_dihapus_jika_tidak_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $lokasi = Lokasi::create([
            'kode_lokasi' => '999',
            'nama_lokasi' => 'Gudang Sementara',
        ]);

        $response = $this->actingAs($admin)->delete(route('data.lokasi.destroy', $lokasi->id));

        $response->assertRedirect(route('data.lokasi.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lokasi', ['id' => $lokasi->id]);
    }

    public function test_lokasi_tidak_dapat_dihapus_jika_masih_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $lokasi = Lokasi::where('kode_lokasi', '111')->first();
        $kategori = Kategori::first();
        $barang = Barang::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Asus']);
        $pic = PenanggungJawab::first();
        $divisi = Divisi::first();

        Aset::create([
            'nama_aset' => 'Laptop Lab Test',
            'sifat_barang' => 'S',
            'kode_aset' => 'EL01S111211202601',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Test',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166.67,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('data.lokasi.destroy', $lokasi->id));

        $response->assertRedirect(route('data.lokasi.index'))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('lokasi', ['id' => $lokasi->id]);
    }
}
