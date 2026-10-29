<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Mitra;
use App\Models\PenanggungJawab;
use App\Models\User;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterMitraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_halaman_master_mitra_dapat_diakses_oleh_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Mitra::create([
            'nama' => 'PT Sumber Rejeki',
            'jenis' => 'distributor',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->get(route('data.mitra.index'));

        $response->assertOk()
            ->assertSeeText('Data Master Toko & Mitra')
            ->assertSeeText('PT Sumber Rejeki');
    }

    public function test_super_admin_dapat_menambahkan_mitra_baru(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.mitra.store'), [
            'nama' => 'PT Mitra Baru Abadi',
            'jenis' => 'distributor',
            'nama_kontak' => 'Budi Santoso',
            'telepon' => '08123456789',
            'email' => 'budi@mitrabaru.com',
            'alamat' => 'Jl. Sudirman No. 12',
            'keterangan' => 'Vendor langganan IT',
            'status' => 'aktif',
        ]);
        $response->assertRedirect(route('data.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mitra', [
            'nama' => 'PT Mitra Baru Abadi',
            'jenis' => 'distributor',
            'nama_kontak' => 'Budi Santoso',
        ]);
    }

    public function test_super_admin_dapat_menambahkan_mitra_baru_via_ajax(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)
            ->postJson(route('data.mitra.store'), [
                'nama' => 'Toko Komputer Harco',
                'jenis' => 'toko',
                'status' => 'aktif',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'nama', 'jenis'])
            ->assertJsonPath('nama', 'Toko Komputer Harco');

        $this->assertDatabaseHas('mitra', ['nama' => 'Toko Komputer Harco']);
    }

    public function test_ajax_penambahan_mitra_duplikat_mengembalikan_error_validasi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Mitra::create([
            'nama' => 'Official Store Dell',
            'jenis' => 'distributor',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('data.mitra.store'), [
                'nama' => 'official store dell',
                'jenis' => 'distributor',
                'status' => 'aktif',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama']);
    }

    public function test_nama_mitra_wajib_diisi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('data.mitra.store'), [
            'nama' => '',
            'jenis' => 'toko',
        ]);

        $response->assertSessionHasErrors(['nama']);
    }

    public function test_super_admin_dapat_mengubah_data_mitra(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $mitra = Mitra::create([
            'nama' => 'PT Mitra Lama',
            'jenis' => 'toko',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->put(route('data.mitra.update', $mitra->id), [
            'nama' => 'PT Mitra Terupdate',
            'jenis' => 'distributor',
            'nama_kontak' => 'Siti',
            'telepon' => '0899999999',
            'email' => 'siti@mitra.com',
            'alamat' => 'Jakarta Selatan',
            'keterangan' => 'Update alamat',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('data.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mitra', [
            'id' => $mitra->id,
            'nama' => 'PT Mitra Terupdate',
            'jenis' => 'distributor',
        ]);
    }

    public function test_update_mitra_tidak_boleh_bentrok_dengan_mitra_lain(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Mitra::create(['nama' => 'Mitra A', 'jenis' => 'toko', 'status' => 'aktif']);
        $mitraB = Mitra::create(['nama' => 'Mitra B', 'jenis' => 'toko', 'status' => 'aktif']);

        $response = $this->actingAs($admin)->put(route('data.mitra.update', $mitraB->id), [
            'nama' => 'mitra a',
            'jenis' => 'toko',
            'status' => 'aktif',
        ]);

        $response->assertSessionHasErrors(['nama']);
    }

    public function test_mitra_dapat_dihapus_jika_tidak_digunakan_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $mitra = Mitra::create(['nama' => 'Mitra Bebas', 'jenis' => 'toko', 'status' => 'aktif']);

        $response = $this->actingAs($admin)->delete(route('data.mitra.destroy', $mitra->id));

        $response->assertRedirect(route('data.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mitra', ['id' => $mitra->id]);
    }

    public function test_mitra_tidak_dapat_dihapus_jika_masih_digunakan_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $mitra = Mitra::create(['nama' => 'Mitra Terpakai', 'jenis' => 'toko', 'status' => 'aktif']);

        $kategori = Kategori::first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $divisi = Divisi::first();
        $lokasi = Lokasi::first();
        $pj = PenanggungJawab::first();

        Aset::create([
            'nama_aset' => 'Laptop Terpakai Mitra',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'mitra_id' => $mitra->id,
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D001211202601',
            'kode_sistem_lama' => 'EL14D001211202601',
            'nomor_urut' => 1,
            'tanggal_pembelian' => '2026-01-01',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 208333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('data.mitra.destroy', $mitra->id));

        $response->assertRedirect(route('data.mitra.index'))
            ->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('mitra', ['id' => $mitra->id]);
    }

    public function test_halaman_master_mitra_ditolak_untuk_viewer(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('data.mitra.index'));

        $response->assertForbidden();
    }

    public function test_viewer_ditolak_menambahkan_mitra_via_form(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->post(route('data.mitra.store'), [
            'nama' => 'Toko Ditolak',
            'jenis' => 'toko',
            'status' => 'aktif',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('mitra', ['nama' => 'Toko Ditolak']);
    }

    public function test_viewer_ditolak_menambahkan_mitra_via_ajax(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($viewer)->postJson(route('data.mitra.store'), [
            'nama' => 'Toko Ajax Ditolak',
            'jenis' => 'toko',
            'status' => 'aktif',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('mitra', ['nama' => 'Toko Ajax Ditolak']);
    }
}
