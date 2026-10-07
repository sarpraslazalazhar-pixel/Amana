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
use App\Services\KodeAsetGenerator;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisiSatuanAuditInternalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Kategori $kategori;
    protected Barang $barang;
    protected PenanggungJawab $pj;
    protected Lokasi $lokasi;
    protected Merk $merk;
    protected Divisi $divisiKpw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin.kpw@test.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
        ]);

        $this->kategori = Kategori::where('kode_kategori', 'EL')->first();
        $this->barang = Barang::where('kategori_id', $this->kategori->id)->first();
        $this->pj = PenanggungJawab::first();
        $this->lokasi = Lokasi::first();
        $this->merk = Merk::firstOrCreate(['nama_merk' => 'Dell']);
        $this->divisiKpw = Divisi::where('kode_divisi', '7')->first();
    }

    public function test_master_divisi_kpw_tersedia_dengan_kode_divisi_tujuh(): void
    {
        $this->assertNotNull($this->divisiKpw);
        $this->assertEquals('Kantor Perwakilan (KPw)', $this->divisiKpw->nama_divisi);
        $this->assertEquals('Aset', $this->divisiKpw->keterangan);
    }

    public function test_generate_kode_aset_dengan_divisi_kpw_menghasilkan_digit_tujuh(): void
    {
        $result = KodeAsetGenerator::generate([
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'divisi_id' => $this->divisiKpw->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-10-06',
        ]);

        $this->assertEquals('7', $result['components']['kode_divisi']);
        // Kode Aset format: Kategori(2) + Barang(2) + Sifat(1) + PIC(3) + Divisi(1) + Cara(1) + Status(1) + Tahun(4) + Urutan(2)
        // Karakter ke-9 (index 8) adalah Divisi
        $this->assertEquals('7', substr($result['kode_aset'], 8, 1));
    }

    public function test_divisi_kpw_dapat_membuat_aset_kelolaan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Mobil Layanan Ambulans KPw Jatim',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiKpw->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-10-06',
            'toko_distributor' => 'Dealer Resmi',
            'jumlah_unit' => 1,
            'harga_satuan' => 250000000,
            'umur_ekonomis_tahun' => 5,
            'merk_id' => $this->merk->id,
            'jenis' => 'kelolaan',
        ]);

        $response->assertRedirect(route('aset.kelolaan'));

        $aset = Aset::where('nama_aset', 'Mobil Layanan Ambulans KPw Jatim')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('kelolaan', $aset->jenis);
        $this->assertEquals('Aset dalam Kelolaan', $aset->klasifikasi);
    }

    public function test_divisi_kpw_dapat_membuat_aset_tetap(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop Kepala Kantor Perwakilan Jatim',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiKpw->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-10-06',
            'toko_distributor' => 'Toko IT Surabaya',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 4,
            'merk_id' => $this->merk->id,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.tetap'));

        $aset = Aset::where('nama_aset', 'Laptop Kepala Kantor Perwakilan Jatim')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);
    }

    public function test_divisi_kpw_default_ke_kelolaan_jika_jenis_tidak_dikirim(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Proyektor KPw Tanpa Input Jenis',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiKpw->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-10-06',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'umur_ekonomis_tahun' => 3,
            'merk_id' => $this->merk->id,
        ]);

        $response->assertRedirect(route('aset.kelolaan'));

        $aset = Aset::where('nama_aset', 'Proyektor KPw Tanpa Input Jenis')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('kelolaan', $aset->jenis);
    }

    public function test_divisi_kpw_dapat_update_jenis_dari_kelolaan_ke_tetap(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Aset KPw Awal Kelolaan',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001711202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiKpw->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-10-06',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'kelolaan',
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals('kelolaan', $aset->jenis);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Aset KPw Diubah Menjadi Tetap',
            'sifat_barang' => 'D',
            'tanggal_pembelian' => '2026-10-06',
            'toko_distributor' => 'Vendor',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiKpw->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);
    }
}
