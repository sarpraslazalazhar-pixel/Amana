<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\RiwayatAset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsetMutasiKodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kategori $kategori;

    protected Barang $barang;

    protected Merk $merk;

    protected Divisi $divisi1;

    protected Divisi $divisi2;

    protected Lokasi $lokasi1;

    protected Lokasi $lokasi2;

    protected PenanggungJawab $pj1;

    protected PenanggungJawab $pj2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'EL']);
        $this->barang = Barang::create(['nama_barang' => 'Laptop', 'kode_barang' => '14', 'kategori_id' => $this->kategori->id]);
        $this->merk = Merk::create(['nama_merk' => 'Lenovo']);

        $this->divisi1 = Divisi::create(['nama_divisi' => 'Sekretariat', 'kode_divisi' => '2']);
        $this->divisi2 = Divisi::create(['nama_divisi' => 'Fundraising', 'kode_divisi' => '3']);

        $this->lokasi1 = Lokasi::create(['nama_lokasi' => 'Lobi Cirendeu', 'kode_lokasi' => '111']);
        $this->lokasi2 = Lokasi::create(['nama_lokasi' => 'Ruang Rapat', 'kode_lokasi' => '112']);

        $this->pj1 = PenanggungJawab::create(['nama' => 'Suryamin', 'kode_pic' => '050', 'divisi_id' => $this->divisi1->id]);
        $this->pj2 = PenanggungJawab::create(['nama' => 'Eko Sugiyanto', 'kode_pic' => '055', 'divisi_id' => $this->divisi2->id]);
    }

    public function test_mutasi_aset_dinamis_otomatis_merubah_kode_aset_dan_mencatat_riwayat()
    {
        // Aset Dinamis awal dengan PIC Suryamin (050), Divisi 2, Urutan 07
        $initialCode = 'EL14D050211202507';
        $aset = Aset::create([
            'nama_aset' => 'ThinkPad T480',
            'sifat_barang' => 'D',
            'kode_aset' => $initialCode,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi1->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2025-01-15',
            'toko_distributor' => 'Lenovo Official',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'nomor_urut' => 7,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // Lakukan Mutasi ke Eko (055) yang ada di Divisi 3
        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $aset->id), [
            'sejak_tanggal' => '2026-09-02',
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi2->id,
            'divisi_id' => $this->divisi2->id,
            'jumlah' => 1,
            'kondisi_persen' => 95,
            'kelengkapan_persen' => 100,
            'keterangan' => 'Pindah tangan kerja ke Fundraising',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $response->assertSessionHas('success');

        $aset->refresh();

        // Kode aset harus berubah ke PIC 055 dan Divisi 3, dengan nomor urut 07 tetap sama!
        $expectedNewCode = 'EL14D055311202507';
        $this->assertEquals($expectedNewCode, $aset->kode_aset);
        $this->assertEquals($initialCode, $aset->kode_aset_lama);
        $this->assertEquals($this->pj2->id, $aset->penanggung_jawab_id);
        $this->assertEquals($this->lokasi2->id, $aset->lokasi_id);
        $this->assertEquals($this->divisi2->id, $aset->divisi_id);

        // Cek tabel riwayat_aset
        $this->assertDatabaseHas('riwayat_aset', [
            'aset_id' => $aset->id,
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi2->id,
            'divisi_id' => $this->divisi2->id,
            'kode_aset_sebelumnya' => $initialCode,
            'kode_aset_baru' => $expectedNewCode,
            'jenis_aksi' => 'mutasi',
        ]);
    }

    public function test_mutasi_aset_statis_otomatis_merubah_kode_lokasi()
    {
        // Aset Statis awal di Lokasi 111 (Lobi Cirendeu)
        $initialCode = 'EL14S111211202503';
        $aset = Aset::create([
            'nama_aset' => 'PC Display Lobi',
            'sifat_barang' => 'S',
            'kode_aset' => $initialCode,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi1->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2025-01-15',
            'toko_distributor' => 'Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 8000000,
            'harga_total' => 8000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 166666.67,
            'nomor_urut' => 3,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // Pindah ke Lokasi 112 (Ruang Rapat)
        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $aset->id), [
            'sejak_tanggal' => '2026-09-02',
            'penanggung_jawab_id' => $this->pj1->id,
            'lokasi_id' => $this->lokasi2->id,
            'divisi_id' => $this->divisi1->id,
            'jumlah' => 1,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'keterangan' => 'Pindah ke ruang rapat',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();

        // Kode ke-4 harus berubah dari 111 ke 112, nomor urut 03 tetap
        $expectedNewCode = 'EL14S112211202503';
        $this->assertEquals($expectedNewCode, $aset->kode_aset);
        $this->assertEquals($initialCode, $aset->kode_aset_lama);
        $this->assertEquals($this->lokasi2->id, $aset->lokasi_id);
    }

    public function test_preview_mutasi_endpoint_menghasilkan_json_yang_benar()
    {
        $aset = Aset::create([
            'nama_aset' => 'Laptop Preview Test',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202501',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi1->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166.67,
            'nomor_urut' => 1,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('aset.preview-mutasi', $aset->id), [
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi1->id,
            'divisi_id' => $this->divisi2->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'changed' => true,
            'old_code' => 'EL14D050211202501',
            'new_code' => 'EL14D055311202501',
            'new_divisi_id' => $this->divisi2->id,
        ]);
    }

    public function test_scan_qr_dengan_kode_lama_berhasil_menemukan_aset_dan_menampilkan_banner()
    {
        $oldCode = 'EL14D050211202501';
        $newCode = 'EL14D055311202501';

        $aset = Aset::create([
            'nama_aset' => 'Aset QR Test',
            'sifat_barang' => 'D',
            'kode_aset' => $newCode,
            'kode_aset_lama' => $oldCode,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi2->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj2->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166.67,
            'nomor_urut' => 1,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        RiwayatAset::create([
            'aset_id' => $aset->id,
            'sejak_tanggal' => '2026-09-01',
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi1->id,
            'divisi_id' => $this->divisi2->id,
            'kode_aset_sebelumnya' => $oldCode,
            'kode_aset_baru' => $newCode,
            'jenis_aksi' => 'mutasi',
        ]);

        // Scan menggunakan kode LAMA
        $response = $this->get(route('public.qr', $oldCode));

        $response->assertOk();
        $response->assertSee('Aset Telah Mengalami Mutasi');
        $response->assertSee($oldCode);
        $response->assertSee($newCode);
        $response->assertSee($aset->nama_aset);
    }

    public function test_mutasi_aset_statis_fallback_divisi_dari_penanggung_jawab_bila_divisi_kosong()
    {
        $initialCode = 'EL14S111211202501';
        $aset = Aset::create([
            'nama_aset' => 'AC Kantor',
            'sifat_barang' => 'S',
            'kode_aset' => $initialCode,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi1->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166.67,
            'nomor_urut' => 1,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // Mutasi PJ ke pj2 (divisi2), tanpa divisi_id
        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $aset->id), [
            'sejak_tanggal' => '2026-09-02',
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi1->id,
            'divisi_id' => '',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();

        $this->assertEquals($this->divisi2->id, $aset->divisi_id);
        $this->assertEquals($this->pj2->id, $aset->penanggung_jawab_id);
    }

    public function test_mutasi_aset_bisa_ubah_divisi_manual_secara_independen()
    {
        $initialCode = 'EL14D050211202501';
        $aset = Aset::create([
            'nama_aset' => 'Laptop Inventaris',
            'sifat_barang' => 'D',
            'kode_aset' => $initialCode,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi1->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166.67,
            'nomor_urut' => 1,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // PJ dipilih pj1 (divisi1), tetapi divisi_id diubah manual ke divisi2
        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $aset->id), [
            'sejak_tanggal' => '2026-09-02',
            'penanggung_jawab_id' => $this->pj1->id,
            'lokasi_id' => $this->lokasi1->id,
            'divisi_id' => $this->divisi2->id,
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();

        $this->assertEquals($this->divisi2->id, $aset->divisi_id);
        $this->assertEquals($this->pj1->id, $aset->penanggung_jawab_id);
    }
}
