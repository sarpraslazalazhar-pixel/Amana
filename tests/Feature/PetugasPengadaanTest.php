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

class PetugasPengadaanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kategori $kategori;

    protected Barang $barang;

    protected Divisi $divisi;

    protected Lokasi $lokasi;

    protected PenanggungJawab $pj;

    protected PenanggungJawab $petugas;

    protected Mitra $mitra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->kategori = Kategori::first();
        $this->barang = Barang::where('kategori_id', $this->kategori->id)->first();
        $this->divisi = Divisi::first();
        $this->lokasi = Lokasi::first();
        $this->pj = PenanggungJawab::first();

        $this->petugas = PenanggungJawab::create([
            'nama' => 'Petugas Pengadaan Khusus',
            'divisi_id' => $this->divisi->id,
            'kode_pic' => '999',
            'status' => 'aktif',
        ]);

        $this->mitra = Mitra::create([
            'nama' => 'Mitra Pengadaan Resmi',
            'jenis' => 'distributor',
            'status' => 'aktif',
        ]);
    }

    public function test_store_aset_menyimpan_petugas_pengadaan_dari_form(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Server Dell PowerEdge',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'petugas_pengadaan_id' => $this->petugas->id,
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'jenis' => 'kelolaan',
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-03-01',
            'jumlah_unit' => 1,
            'harga_satuan' => '25.000.000',
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
        ]);

        $response->assertRedirect(route('aset.kelolaan'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'Server Dell PowerEdge',
            'petugas_pengadaan_id' => $this->petugas->id,
            'mitra_id' => $this->mitra->id,
        ]);
    }

    public function test_store_aset_tanpa_petugas_pengadaan_mengisi_default_dari_user_login(): void
    {
        // Hubungkan admin dengan sebuah PenanggungJawab
        $adminPj = PenanggungJawab::create([
            'nama' => 'Admin Sebagai Penanggung Jawab',
            'user_id' => $this->admin->id,
            'divisi_id' => $this->divisi->id,
            'kode_pic' => '888',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'PC All-in-One HP',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            // petugas_pengadaan_id tidak dikirim
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'jenis' => 'kelolaan',
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-03-01',
            'jumlah_unit' => 1,
            'harga_satuan' => '15.000.000',
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
        ]);

        $response->assertRedirect(route('aset.kelolaan'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'PC All-in-One HP',
            'petugas_pengadaan_id' => $adminPj->id,
        ]);
    }

    public function test_store_aset_tanpa_petugas_pengadaan_dan_tanpa_pj_terkait_simpan_null(): void
    {
        // Admin tidak punya record di PenanggungJawab dengan user_id = admin->id
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Printer Epson L3210',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'petugas_pengadaan_id' => '',
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'jenis' => 'kelolaan',
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-03-01',
            'jumlah_unit' => 1,
            'harga_satuan' => '3.000.000',
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
        ]);

        $response->assertRedirect(route('aset.kelolaan'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'Printer Epson L3210',
            'petugas_pengadaan_id' => null,
        ]);
    }

    public function test_update_aset_petugas_pengadaan_dapat_diubah(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Aset Uji Petugas Pengadaan',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'petugas_pengadaan_id' => $this->petugas->id,
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D001211202602',
            'kode_sistem_lama' => 'EL14D001211202602',
            'nomor_urut' => 2,
            'tanggal_pembelian' => '2026-01-01',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 208333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $pjBaru = PenanggungJawab::create([
            'nama' => 'Petugas Pengadaan Baru',
            'divisi_id' => $this->divisi->id,
            'kode_pic' => '998',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Aset Uji Petugas Pengadaan Updated',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'petugas_pengadaan_id' => $pjBaru->id,
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'jenis' => 'tetap',
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-01-01',
            'jumlah_unit' => 1,
            'harga_satuan' => '10.000.000',
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
        ]);

        $response->assertRedirect(route('aset.show', $aset->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('aset', [
            'id' => $aset->id,
            'petugas_pengadaan_id' => $pjBaru->id,
        ]);
    }

    public function test_show_aset_menampilkan_petugas_pengadaan(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Aset Show Petugas Pengadaan',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'petugas_pengadaan_id' => $this->petugas->id,
            'mitra_id' => $this->mitra->id,
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D001211202603',
            'kode_sistem_lama' => 'EL14D001211202603',
            'nomor_urut' => 3,
            'tanggal_pembelian' => '2026-01-01',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 208333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $aset->id));

        $response->assertOk()
            ->assertSeeText('Petugas Pengadaan Khusus')
            ->assertSeeText('Mitra Pengadaan Resmi');
    }
}
