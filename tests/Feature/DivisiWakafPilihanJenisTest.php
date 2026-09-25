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

class DivisiWakafPilihanJenisTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Kategori $kategori;
    protected Barang $barang;
    protected PenanggungJawab $pj;
    protected Lokasi $lokasi;
    protected Merk $merk;
    protected Divisi $divisiWakaf;
    protected Divisi $divisiProgram;
    protected Divisi $divisiDireksi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin.wakaf@test.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
        ]);

        $this->kategori = Kategori::where('kode_kategori', 'EL')->first();
        $this->barang = Barang::where('kategori_id', $this->kategori->id)->first();
        $this->pj = PenanggungJawab::first();
        $this->lokasi = Lokasi::first();
        $this->merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);

        $this->divisiWakaf = Divisi::where('kode_divisi', '6')->first();
        $this->divisiProgram = Divisi::where('kode_divisi', '5')->first();
        $this->divisiDireksi = Divisi::where('kode_divisi', '1')->first();
    }

    public function test_divisi_wakaf_dapat_membuat_aset_kelolaan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Ambulans Wakaf Operasional',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiWakaf->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Dealer Mobil',
            'jumlah_unit' => 1,
            'harga_satuan' => 250000000,
            'umur_ekonomis_tahun' => 5,
            'merk_id' => $this->merk->id,
            'jenis' => 'kelolaan',
        ]);

        $response->assertRedirect(route('aset.kelolaan'));

        $aset = Aset::where('nama_aset', 'Ambulans Wakaf Operasional')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('kelolaan', $aset->jenis);
        $this->assertEquals('Aset dalam Kelolaan', $aset->klasifikasi);
    }

    public function test_divisi_wakaf_dapat_membuat_aset_tetap_hak_nazir(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop Staf Nazir Wakaf',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiWakaf->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Toko IT',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 4,
            'merk_id' => $this->merk->id,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.tetap'));

        $aset = Aset::where('nama_aset', 'Laptop Staf Nazir Wakaf')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);
    }

    public function test_divisi_wakaf_default_ke_kelolaan_jika_jenis_tidak_dikirim(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Aset Wakaf Tanpa Input Jenis',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiWakaf->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'umur_ekonomis_tahun' => 3,
            'merk_id' => $this->merk->id,
        ]);

        $response->assertRedirect(route('aset.kelolaan'));

        $aset = Aset::where('nama_aset', 'Aset Wakaf Tanpa Input Jenis')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('kelolaan', $aset->jenis);
    }

    public function test_divisi_wakaf_dapat_update_jenis_dari_kelolaan_ke_tetap(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Aset Wakaf Awal Kelolaan',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'kelolaan',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Aset Wakaf Diubah ke Tetap Hak Nazir',
            'divisi_id' => $this->divisiWakaf->id,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
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

    public function test_divisi_program_selalu_kelolaan_meskipun_request_tetap(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Aset Program Tes',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiProgram->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'umur_ekonomis_tahun' => 3,
            'merk_id' => $this->merk->id,
            'jenis' => 'tetap', // Dipaksa tetap, tapi sistem harus mengunci ke kelolaan
        ]);

        $response->assertRedirect(route('aset.kelolaan'));

        $aset = Aset::where('nama_aset', 'Aset Program Tes')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('kelolaan', $aset->jenis);
    }

    public function test_divisi_direksi_selalu_tetap_meskipun_request_kelolaan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Aset Direksi Tes',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiDireksi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 4,
            'merk_id' => $this->merk->id,
            'jenis' => 'kelolaan', // Dipaksa kelolaan, tapi sistem harus mengunci ke tetap
        ]);

        $response->assertRedirect(route('aset.tetap'));

        $aset = Aset::where('nama_aset', 'Aset Direksi Tes')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('tetap', $aset->jenis);
    }

    public function test_scope_klasifikasi_memfilter_aset_wakaf_sesuai_jenis(): void
    {
        $asetKelolaan = Aset::create([
            'nama_aset' => 'Wakaf Kelolaan 1',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202602',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'kelolaan',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $asetTetap = Aset::create([
            'nama_aset' => 'Wakaf Tetap Hak Nazir 1',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202603',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'tetap',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $kelolaanIds = Aset::klasifikasi('kelolaan')->pluck('id')->all();
        $tetapIds = Aset::klasifikasi('tetap')->pluck('id')->all();

        $this->assertContains($asetKelolaan->id, $kelolaanIds);
        $this->assertNotContains($asetTetap->id, $kelolaanIds);

        $this->assertContains($asetTetap->id, $tetapIds);
        $this->assertNotContains($asetKelolaan->id, $tetapIds);
    }

    public function test_form_create_dan_edit_menampilkan_opsi_khusus_wakaf(): void
    {
        $responseCreate = $this->actingAs($this->admin)->get(route('aset.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Pilihan Khusus Wakaf');
        $responseCreate->assertSee('Tetap (Hak Nazir)');

        $aset = Aset::create([
            'nama_aset' => 'Aset Form Edit Tes',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202604',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'tetap',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $responseEdit = $this->actingAs($this->admin)->get(route('aset.edit', $aset->id));
        $responseEdit->assertStatus(200);
        $responseEdit->assertSee('Pilihan Khusus Wakaf');
        $responseEdit->assertSee('Tetap (Hak Nazir)');
    }

    public function test_mutasi_aset_ke_divisi_wakaf_dapat_memilih_jenis_tetap_hak_nazir(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Mobil Mutasi ke Wakaf',
            'sifat_barang' => 'D',
            'kode_aset' => 'KD01D001111202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiDireksi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 200000000,
            'harga_total' => 200000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 3333333.33,
            'jenis' => 'tetap',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $aset->id), [
            'sejak_tanggal' => '2026-09-25',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'divisi_id' => $this->divisiWakaf->id,
            'jenis' => 'tetap', // Dipilih Aset Tetap Hak Nazir
            'keterangan' => 'Mutasi operasional ke Divisi Wakaf via Hak Nazir',
        ]);

        $response->assertRedirect();

        $aset->refresh();
        $this->assertEquals($this->divisiWakaf->id, $aset->divisi_id);
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);

        $riwayat = \App\Models\RiwayatAset::where('aset_id', $aset->id)->where('jenis_aksi', 'mutasi')->latest()->first();
        $this->assertNotNull($riwayat);
        $this->assertStringContainsString('Mutasi operasional ke Divisi Wakaf via Hak Nazir', $riwayat->keterangan);
    }

    public function test_update_aset_merubah_jenis_wakaf_mencatat_di_riwayat(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Komputer Wakaf Uji Riwayat',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202609',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'kelolaan',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Komputer Wakaf Uji Riwayat',
            'divisi_id' => $this->divisiWakaf->id,
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $this->pj->id,
            'lokasi_id' => $this->lokasi->id,
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));

        $riwayat = \App\Models\RiwayatAset::where('aset_id', $aset->id)->where('jenis_aksi', 'mutasi')->latest()->first();
        $this->assertNotNull($riwayat);
        $this->assertStringContainsString("Jenis aset dialihkan dari 'Kelolaan' ke 'Tetap (Hak Nazir)'", $riwayat->keterangan);
    }

    public function test_halaman_detail_menampilkan_pilihan_jenis_di_modal_mutasi_dan_badge_di_riwayat(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Aset Wakaf Detail Show Test',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL11D001611202610',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisiWakaf->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-25',
            'toko_distributor' => 'Vendor',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'jenis' => 'tetap',
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $aset->id));
        $response->assertStatus(200);
        $response->assertSee('Pilihan Khusus Wakaf');
        $response->assertSee('Tetap (Hak Nazir)');
    }
}
