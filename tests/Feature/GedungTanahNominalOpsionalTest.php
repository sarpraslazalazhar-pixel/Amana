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

class GedungTanahNominalOpsionalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kategori $kategoriGD;

    protected Kategori $kategoriTN;

    protected Kategori $kategoriEL;

    protected Barang $barangGD;

    protected Barang $barangTN;

    protected Barang $barangEL;

    protected Divisi $divisi;

    protected Merk $merk;

    protected Lokasi $lokasi;

    protected PenanggungJawab $pj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create(['role' => 'super_admin']);

        $this->kategoriGD = Kategori::where('kode_kategori', 'GD')->first();
        $this->kategoriTN = Kategori::where('kode_kategori', 'TN')->first();
        $this->kategoriEL = Kategori::where('kode_kategori', 'EL')->first();

        $this->barangGD = Barang::create(['kategori_id' => $this->kategoriGD->id, 'kode_barang' => '01', 'nama_barang' => 'Gedung Kantor']);
        $this->barangTN = Barang::create(['kategori_id' => $this->kategoriTN->id, 'kode_barang' => '01', 'nama_barang' => 'Tanah Wakaf']);
        $this->barangEL = Barang::where('kategori_id', $this->kategoriEL->id)->first();

        $this->divisi = Divisi::where('kode_divisi', '2')->first();
        $this->merk = Merk::firstOrCreate(['nama_merk' => 'Umum']);
        $this->lokasi = Lokasi::first();
        $this->pj = PenanggungJawab::first();
    }

    public function test_tambah_aset_gedung_tanpa_nominal_dan_umur_berhasil(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Gedung Kantor Pusat Al Azhar',
            'sifat_barang' => 'S',
            'kategori_id' => $this->kategoriGD->id,
            'barang_id' => $this->barangGD->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-15',
            'toko_distributor' => 'Yayasan Pesantren Islam',
            'jumlah_unit' => 1,
            // harga_satuan dan umur_ekonomis_tahun tidak diisi (opsional)
        ]);

        $response->assertRedirect();

        $aset = Aset::where('nama_aset', 'Gedung Kantor Pusat Al Azhar')->first();
        $this->assertNotNull($aset);
        $this->assertEquals(0, (float) $aset->harga_satuan);
        $this->assertEquals(0, (float) $aset->harga_total);
        $this->assertEquals(0, $aset->umur_ekonomis_tahun);
        $this->assertEquals(0, (float) $aset->penyusutan_per_bulan);
    }

    public function test_tambah_aset_tanah_tanpa_nominal_berhasil_dan_umur_default_nol(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Tanah Wakaf Produktif Cirendeu',
            'sifat_barang' => 'S',
            'kategori_id' => $this->kategoriTN->id,
            'barang_id' => $this->barangTN->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '2',
            'status_barang' => '1',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-02-10',
            'toko_distributor' => 'Wakif H. Ahmad',
            'jumlah_unit' => 1,
            'harga_satuan' => '',
            'umur_ekonomis_tahun' => 0,
        ]);

        $response->assertRedirect();

        $aset = Aset::where('nama_aset', 'Tanah Wakaf Produktif Cirendeu')->first();
        $this->assertNotNull($aset);
        $this->assertEquals(0, (float) $aset->harga_satuan);
        $this->assertEquals(0, (float) $aset->harga_total);
        $this->assertEquals(0, $aset->umur_ekonomis_tahun);
        $this->assertEquals(0, (float) $aset->penyusutan_per_bulan);
    }

    public function test_tambah_aset_elektronik_tanpa_nominal_tetap_wajib_dan_gagal_validasi(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop MacBook Air M3',
            'sifat_barang' => 'D',
            'kategori_id' => $this->kategoriEL->id,
            'barang_id' => $this->barangEL->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'iBox',
            'jumlah_unit' => 1,
            // harga_satuan sengaja dikosongkan
            'harga_satuan' => '',
            'umur_ekonomis_tahun' => 4,
        ]);

        $response->assertSessionHasErrors(['harga_satuan']);
    }

    public function test_update_aset_gedung_mengosongkan_harga_berhasil(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Gedung Lama',
            'sifat_barang' => 'S',
            'kode_aset' => 'GD-01-S-008-100-2-1-1-2026-0001',
            'kategori_id' => $this->kategoriGD->id,
            'barang_id' => $this->barangGD->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => '0001',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Developer',
            'jumlah_unit' => 1,
            'harga_satuan' => 500000000,
            'harga_total' => 500000000,
            'umur_ekonomis_tahun' => 20,
            'penyusutan_per_bulan' => 2083333.33,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Gedung Lama Diperbarui',
            'kategori_id' => $this->kategoriGD->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Developer',
            'jumlah_unit' => 1,
            'harga_satuan' => '',
            'umur_ekonomis_tahun' => '',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));

        $aset->refresh();
        $this->assertEquals(0, (float) $aset->harga_satuan);
        $this->assertEquals(0, (float) $aset->harga_total);
        $this->assertEquals(0, $aset->umur_ekonomis_tahun);
        $this->assertEquals(0, (float) $aset->penyusutan_per_bulan);
    }

    public function test_detail_aset_dan_pdf_kartu_aset_untuk_nominal_nol_tampil_tanpa_error(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Tanah Percobaan',
            'sifat_barang' => 'S',
            'kode_aset' => 'TN-01-S-008-100-2-2-1-2026-0002',
            'kategori_id' => $this->kategoriTN->id,
            'barang_id' => $this->barangTN->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '2',
            'status_barang' => '1',
            'nomor_urut' => '0002',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Wakif',
            'jumlah_unit' => 1,
            'harga_satuan' => 0,
            'harga_total' => 0,
            'umur_ekonomis_tahun' => 0,
            'penyusutan_per_bulan' => 0,
            'created_by' => $this->admin->id,
        ]);

        // Test halaman show
        $showResponse = $this->actingAs($this->admin)->get(route('aset.show', $aset->id));
        $showResponse->assertOk()
            ->assertSeeText('Belum dinilai');

        // Test halaman PDF
        $pdfResponse = $this->actingAs($this->admin)->get(route('aset.pdf', $aset->id));
        $pdfResponse->assertOk();

        // Test halaman tabel daftar aset
        $indexResponse = $this->actingAs($this->admin)->get(route('aset.tetap'));
        $indexResponse->assertOk();
    }
}
