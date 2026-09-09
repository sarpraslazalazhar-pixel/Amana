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

class MasterKategoriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_halaman_master_kategori_dapat_diakses_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('data.kategori.index'));

        $response->assertOk()
            ->assertSeeText('Kategori & Jenis Barang')
            ->assertSeeText('Elektronik')
            ->assertSeeText('Furniture')
            ->assertSeeText('Kendaraan');
    }

    public function test_super_admin_dapat_menambahkan_kategori_baru(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.kategori.store'), [
            'kode_kategori' => 'PR',
            'nama_kategori' => 'Peralatan & Mesin',
            'keterangan' => 'Kategori peralatan industri dan mesin kerja',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategori', [
            'kode_kategori' => 'PR',
            'nama_kategori' => 'Peralatan & Mesin',
        ]);
    }

    public function test_super_admin_dapat_mengubah_kategori(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'EL')->first();

        $response = $this->actingAs($admin)->put(route('data.kategori.update', $kategori->id), [
            'kode_kategori' => 'EL',
            'nama_kategori' => 'Elektronik & Perangkat Komputer',
            'keterangan' => 'Peralatan elektronik dan IT yang diperbarui',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'nama_kategori' => 'Elektronik & Perangkat Komputer',
        ]);
    }

    public function test_show_mengembalikan_json_kategori_dan_daftar_barang(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'EL')->first();

        $response = $this->actingAs($admin)->get(route('data.kategori.show', $kategori->id));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('kategori.kode_kategori', 'EL')
            ->assertJsonPath('next_kode_barang', '56');
    }

    public function test_super_admin_dapat_menambahkan_jenis_barang_baru(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'EL')->first();

        $response = $this->actingAs($admin)->post(route('data.kategori.barang.store', $kategori->id), [
            'kode_barang' => '56',
            'nama_barang' => 'Smart Speaker AI',
            'keterangan' => 'Speaker pintar dengan asisten AI',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barang', [
            'kategori_id' => $kategori->id,
            'kode_barang' => '56',
            'nama_barang' => 'Smart Speaker AI',
        ]);
    }

    public function test_super_admin_dapat_mengubah_jenis_barang(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $barang = Barang::where('kode_barang', '01')->whereHas('kategori', function ($q) {
            $q->where('kode_kategori', 'EL');
        })->first();

        $response = $this->actingAs($admin)->put(route('data.kategori.barang.update', $barang->id), [
            'kode_barang' => '01',
            'nama_barang' => 'Air Conditioner (AC) Inverter',
            'keterangan' => 'Pendingin ruangan inverter hemat energi',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barang', [
            'id' => $barang->id,
            'nama_barang' => 'Air Conditioner (AC) Inverter',
        ]);
    }

    public function test_barang_dapat_dihapus_jika_tidak_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'KD')->first();
        $barang = Barang::create([
            'kategori_id' => $kategori->id,
            'kode_barang' => '99',
            'nama_barang' => 'Kendaraan Uji Hapus',
        ]);

        $response = $this->actingAs($admin)->delete(route('data.kategori.barang.destroy', $barang->id));

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('barang', ['id' => $barang->id]);
    }

    public function test_barang_tidak_dapat_dihapus_jika_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::first();
        $pic = PenanggungJawab::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Daikin']);

        Aset::create([
            'nama_aset' => 'AC Daikin Ruang Rapat',
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
            'toko_distributor' => 'Toko Elektronik',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 83333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('data.kategori.barang.destroy', $barang->id));

        $response->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('barang', ['id' => $barang->id]);
    }

    public function test_kategori_tidak_dapat_dihapus_jika_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::first();
        $pic = PenanggungJawab::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Daikin']);

        Aset::create([
            'nama_aset' => 'AC Daikin Ruang Direksi',
            'sifat_barang' => 'S',
            'kode_aset' => 'EL01S111211202602',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 2,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Elektronik',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 83333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('data.kategori.destroy', $kategori->id));

        $response->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('kategori', ['id' => $kategori->id]);
    }
}
