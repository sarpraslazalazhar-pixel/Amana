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

class MasterPenanggungJawabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_halaman_master_penanggung_jawab_dapat_diakses_oleh_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('data.penanggung-jawab.index'));

        $response->assertOk()
            ->assertSeeText('Penanggung Jawab (PIC / Amil)')
            ->assertSeeText('Iwan Rahmat')
            ->assertSeeText('Total Amil / PIC');
    }

    public function test_pencarian_dan_filter_penanggung_jawab(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('data.penanggung-jawab.index', ['search' => 'Suryamin']));

        $response->assertOk()
            ->assertSeeText('Suryamin')
            ->assertSeeText('050');
    }

    public function test_super_admin_dapat_menambahkan_pic_baru(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $divisi = Divisi::where('kode_divisi', '3')->first();

        $response = $this->actingAs($admin)->post(route('data.penanggung-jawab.store'), [
            'kode_pic' => '301',
            'nama' => 'Amil Baru Penguji',
            'divisi_id' => $divisi->id,
            'jabatan' => 'Staff Kampanye Digital',
            'telepon' => '081289301301',
            'email' => 'amil.baru@alazhar.org',
            'status' => 'aktif',
            'alamat' => 'Jakarta Selatan',
        ]);

        $response->assertRedirect(route('data.penanggung-jawab.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('penanggung_jawab', [
            'kode_pic' => '301',
            'nama' => 'Amil Baru Penguji',
            'divisi_id' => $divisi->id,
            'status' => 'aktif',
        ]);
    }

    public function test_super_admin_dapat_mengubah_data_pic(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $pic = PenanggungJawab::where('kode_pic', '050')->first();

        $response = $this->actingAs($admin)->put(route('data.penanggung-jawab.update', $pic->id), [
            'kode_pic' => '050',
            'nama' => 'Suryamin (Dipromosikan)',
            'divisi_id' => $pic->divisi_id,
            'jabatan' => 'Koordinator CRM & Aset Nasional',
            'telepon' => '081289050050',
            'email' => 'suryamin.senior@alazhar.org',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('data.penanggung-jawab.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('penanggung_jawab', [
            'id' => $pic->id,
            'nama' => 'Suryamin (Dipromosikan)',
            'jabatan' => 'Koordinator CRM & Aset Nasional',
        ]);
    }

    public function test_show_mengembalikan_json_aset_yang_dipegang_pic(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $kategori = Kategori::first();
        $barang = Barang::first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);

        Aset::create([
            'nama_aset' => 'Laptop ThinkPad PIC 050',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050212202601',
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
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 12000000,
            'harga_total' => 12000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 200000,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('data.penanggung-jawab.show', $pic->id));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('pic.kode_pic', '050')
            ->assertJsonPath('pic.total_aset', 1)
            ->assertJsonPath('aset.0.nama_aset', 'Laptop ThinkPad PIC 050');
    }

    public function test_pic_dapat_dihapus_jika_tidak_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $pic = PenanggungJawab::create([
            'kode_pic' => '999',
            'nama' => 'Amil Uji Coba Hapus',
            'status' => 'non_aktif',
        ]);

        $response = $this->actingAs($admin)->delete(route('data.penanggung-jawab.destroy', $pic->id));

        $response->assertRedirect(route('data.penanggung-jawab.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('penanggung_jawab', ['id' => $pic->id]);
    }

    public function test_pic_tidak_dapat_dihapus_jika_masih_memiliki_aset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $kategori = Kategori::first();
        $barang = Barang::first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);

        Aset::create([
            'nama_aset' => 'Laptop Inventaris Tetap',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050212202602',
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
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 12000000,
            'harga_total' => 12000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 200000,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('data.penanggung-jawab.destroy', $pic->id));

        $response->assertRedirect(route('data.penanggung-jawab.index'))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('penanggung_jawab', ['id' => $pic->id]);
    }
}
