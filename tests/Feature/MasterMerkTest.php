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

class MasterMerkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_halaman_master_merk_dapat_diakses_oleh_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Merk::create(['nama_merk' => 'Lenovo']);

        $response = $this->actingAs($admin)->get(route('data.merk.index'));

        $response->assertOk()
            ->assertSeeText('Data Master Merk')
            ->assertSeeText('Daftar Master Merk')
            ->assertSeeText('Lenovo');
    }

    public function test_super_admin_dapat_menambahkan_merk_baru(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.merk.store'), [
            'nama_merk' => 'Dell',
        ]);

        $response->assertRedirect(route('data.merk.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('merk', ['nama_merk' => 'Dell']);
    }

    public function test_super_admin_dapat_menambahkan_merk_baru_via_ajax(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)
            ->postJson(route('data.merk.store'), ['nama_merk' => 'Epson']);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'nama_merk'])
            ->assertJsonPath('nama_merk', 'Epson');

        $this->assertDatabaseHas('merk', ['nama_merk' => 'Epson']);
    }

    public function test_ajax_penambahan_merk_duplikat_mengembalikan_error_validasi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Merk::create(['nama_merk' => 'HP']);

        $response = $this->actingAs($admin)
            ->postJson(route('data.merk.store'), ['nama_merk' => 'HP']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_merk']);
    }

    public function test_nama_merk_wajib_diisi_dan_tidak_boleh_kosong(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.merk.store'), [
            'nama_merk' => '',
        ]);

        $response->assertSessionHasErrors(['nama_merk']);
        $this->assertDatabaseCount('merk', 0);
    }

    public function test_super_admin_dapat_mengubah_data_merk(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $merk = Merk::create(['nama_merk' => 'Seiko Epson']);

        $response = $this->actingAs($admin)->put(route('data.merk.update', $merk->id), [
            'nama_merk' => 'Epson',
        ]);

        $response->assertRedirect(route('data.merk.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('merk', ['id' => $merk->id, 'nama_merk' => 'Epson']);
    }

    public function test_update_merk_tidak_boleh_bentrok_dengan_merk_lain(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Merk::create(['nama_merk' => 'Canon']);
        $merk = Merk::create(['nama_merk' => 'Brother']);

        $response = $this->actingAs($admin)->put(route('data.merk.update', $merk->id), [
            'nama_merk' => 'Canon',
        ]);

        $response->assertSessionHasErrors(['nama_merk']);
        $this->assertDatabaseHas('merk', ['id' => $merk->id, 'nama_merk' => 'Brother']);
    }

    public function test_merk_dapat_dihapus_jika_tidak_digunakan_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $merk = Merk::create(['nama_merk' => 'Merk Tak Terpakai']);

        $response = $this->actingAs($admin)->delete(route('data.merk.destroy', $merk->id));

        $response->assertRedirect(route('data.merk.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('merk', ['id' => $merk->id]);
    }

    public function test_merk_tidak_dapat_dihapus_jika_masih_digunakan_aset(): void
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

        $response = $this->actingAs($admin)->delete(route('data.merk.destroy', $merk->id));

        $response->assertRedirect(route('data.merk.index'))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('merk', ['id' => $merk->id]);
    }

    public function test_halaman_master_merk_ditolak_untuk_viewer(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('data.merk.index'));

        $response->assertForbidden();
    }

    public function test_viewer_ditolak_menambahkan_merk_via_form(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->post(route('data.merk.store'), [
            'nama_merk' => 'Viewer Brand',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('merk', ['nama_merk' => 'Viewer Brand']);
    }

    public function test_viewer_ditolak_menambahkan_merk_via_ajax(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->postJson(route('data.merk.store'), ['nama_merk' => 'Viewer Brand']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('merk', ['nama_merk' => 'Viewer Brand']);
    }
}
