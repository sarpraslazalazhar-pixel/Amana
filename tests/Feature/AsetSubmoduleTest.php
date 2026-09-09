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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AsetSubmoduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Aset $aset;

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

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Asus']);
        $this->lokasi1 = Lokasi::create(['nama_lokasi' => 'Ruang IT', 'kode_lokasi' => 'IT01']);
        $this->lokasi2 = Lokasi::create(['nama_lokasi' => 'Ruang HR', 'kode_lokasi' => 'HR01']);
        $this->pj1 = PenanggungJawab::create(['nama' => 'Budi Santoso', 'jabatan' => 'Staff IT']);
        $this->pj2 = PenanggungJawab::create(['nama' => 'Siti Rahma', 'jabatan' => 'Staff HR']);

        $this->aset = Aset::create([
            'nama_aset' => 'Laptop ROG',
            'kode_aset' => 'ELK-2026-IT01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'harga_total' => 15000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 250000,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_mutasi_aset_dengan_format_lengkap_amana()
    {
        $response = $this->actingAs($this->admin)->post(route('aset.mutasi', $this->aset->id), [
            'sejak_tanggal' => '2026-08-26',
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi2->id,
            'jumlah' => 1,
            'kondisi_persen' => 95,
            'kelengkapan_persen' => 100,
            'keterangan' => 'Mutasi ke divisi HR',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $response->assertSessionHas('success');

        // Pastikan penanggung jawab & lokasi langsung berubah di Aset
        $this->aset->refresh();
        $this->assertEquals($this->pj2->id, $this->aset->penanggung_jawab_id);
        $this->assertEquals($this->lokasi2->id, $this->aset->lokasi_id);

        // Pastikan tercatat di riwayat_aset dengan format Amana.md
        $this->assertDatabaseHas('riwayat_aset', [
            'aset_id' => $this->aset->id,
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi2->id,
            'jumlah' => 1,
            'kondisi_persen' => 95,
            'kelengkapan_persen' => 100,
            'keterangan' => 'Mutasi ke divisi HR',
        ]);
    }

    public function test_tambah_agenda_berulang_dan_toggle_status()
    {
        // 1. Agenda Mingguan
        $response = $this->actingAs($this->admin)->post(route('aset.agenda.store', $this->aset->id), [
            'tipe_agenda' => 'mingguan',
            'nama_agenda' => 'Pembersihan Debu Mingguan',
            'hari' => 'jumat',
            'biaya_estimasi' => 50000,
            'keterangan' => 'Setiap hari Jumat sore',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));

        $this->assertDatabaseHas('agenda_aset', [
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'mingguan',
            'nama_agenda' => 'Pembersihan Debu Mingguan',
            'hari' => 'jumat',
            'status' => 'pending',
        ]);

        $agenda = $this->aset->agenda()->first();

        // Toggle to Selesai
        $toggleResponse = $this->actingAs($this->admin)->patch(route('aset.agenda.toggle', $agenda->id));
        $toggleResponse->assertRedirect();

        $agenda->refresh();
        $this->assertEquals('selesai', $agenda->status);

        // 2. Agenda Bulanan (Tanggal 1-28)
        $responseBulanan = $this->actingAs($this->admin)->post(route('aset.agenda.store', $this->aset->id), [
            'tipe_agenda' => 'bulanan',
            'nama_agenda' => 'Ganti Filter Oli Bulanan',
            'tanggal_hari' => 15,
            'biaya_estimasi' => 150000,
        ]);
        $responseBulanan->assertRedirect(route('aset.show', $this->aset->id));
        $this->assertDatabaseHas('agenda_aset', [
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'bulanan',
            'tanggal_hari' => 15,
        ]);

        // 3. Agenda Tahunan (Tanggal & Bulan tanpa tahun)
        $responseTahunan = $this->actingAs($this->admin)->post(route('aset.agenda.store', $this->aset->id), [
            'tipe_agenda' => 'tahunan',
            'nama_agenda' => 'Perpanjangan STNK & Pajak',
            'tanggal_hari' => 17,
            'bulan' => 8,
            'biaya_estimasi' => 2500000,
        ]);
        $responseTahunan->assertRedirect(route('aset.show', $this->aset->id));
        $this->assertDatabaseHas('agenda_aset', [
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tahunan',
            'nama_agenda' => 'Perpanjangan STNK & Pajak',
            'tanggal_hari' => 17,
            'bulan' => 8,
        ]);
    }

    public function test_catat_pengeluaran_biaya_keuangan()
    {
        // Pengeluaran Biaya Servis / Perawatan
        $response = $this->actingAs($this->admin)->post(route('aset.keuangan.store', $this->aset->id), [
            'tanggal' => '2026-08-20',
            'jenis_transaksi' => 'Upgrade RAM 32GB',
            'nominal' => 1200000,
            'keterangan' => 'Beli di Official Store',
        ]);
        $response->assertRedirect(route('aset.show', $this->aset->id));

        $this->assertDatabaseHas('keuangan_aset', [
            'aset_id' => $this->aset->id,
            'tipe' => 'pengeluaran',
            'jenis_transaksi' => 'Upgrade RAM 32GB',
            'nominal' => 1200000,
        ]);
    }

    public function test_lapor_jurnal_kejadian_dengan_lampiran()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('nota_perbaikan.pdf', 100);

        $response = $this->actingAs($this->admin)->post(route('aset.jurnal.store', $this->aset->id), [
            'tanggal' => '2026-08-25',
            'kejadian' => 'Engsel layar diperbaiki di service center',
            'lampiran' => $file,
            'tingkat_kerusakan' => 'sedang',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));

        $this->assertDatabaseHas('jurnal_aset', [
            'aset_id' => $this->aset->id,
            'kejadian' => 'Engsel layar diperbaiki di service center',
            'status_penanganan' => 'belum_ditangani',
        ]);

        $jurnal = $this->aset->jurnal()->first();
        $this->assertNotNull($jurnal->lampiran);
        Storage::disk('public')->assertExists($jurnal->lampiran);

        // Update status ke selesai
        $updateResponse = $this->actingAs($this->admin)->patch(route('aset.jurnal.status', $jurnal->id), [
            'status_penanganan' => 'selesai',
        ]);
        $updateResponse->assertRedirect();

        $jurnal->refresh();
        $this->assertEquals('selesai', $jurnal->status_penanganan);
    }

    public function test_update_aset_tanpa_foto(): void
    {
        $response = $this->actingAs($this->admin)->put(route('aset.update', $this->aset->id), [
            'nama_aset' => 'Laptop ROG Updated',
            'kategori_id' => $this->aset->kategori_id,
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 2,
            'harga_satuan' => 16000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 1000000,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $this->aset->refresh();
        $this->assertEquals('Laptop ROG Updated', $this->aset->nama_aset);
        $this->assertEquals(2, $this->aset->jumlah_unit);
        $this->assertEquals(32000000, $this->aset->harga_total);
    }

    public function test_update_aset_dengan_foto(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('laptop_baru.jpg', 600, 400);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $this->aset->id), [
            'nama_aset' => 'Laptop ROG Foto Baru',
            'kategori_id' => $this->aset->kategori_id,
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'jenis' => 'tetap',
            'foto_utama' => $file,
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $this->aset->refresh();
        $this->assertNotNull($this->aset->foto_utama);
        Storage::disk('public')->assertExists($this->aset->foto_utama);
    }

    public function test_show_aset_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.show', $this->aset->id));
        $response->assertOk();
        $response->assertSeeText($this->aset->nama_aset);
    }

    public function test_store_aset_otomatis_jenis_kelolaan_untuk_divisi_program_dan_wakaf(): void
    {
        $kategori = Kategori::first();
        $barang = Barang::create(['kategori_id' => $kategori->id, 'kode_barang' => '99', 'nama_barang' => 'Item Program']);
        $divisiProgram = Divisi::create(['kode_divisi' => '5', 'nama_divisi' => 'Program']);
        $divisiWakaf = Divisi::create(['kode_divisi' => '6', 'nama_divisi' => 'Wakaf']);

        // 1. Test Divisi 5 (Program) -> otomatis kelolaan
        $response1 = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Aset Program Bantuan',
            'sifat_barang' => 'D',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisiProgram->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-08-27',
            'toko_distributor' => 'Vendor Program',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'umur_ekonomis_tahun' => 3,
        ]);

        $response1->assertRedirect(route('aset.kelolaan'));
        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'Aset Program Bantuan',
            'divisi_id' => $divisiProgram->id,
            'jenis' => 'kelolaan',
        ]);

        // 2. Test Divisi 6 (Wakaf) -> otomatis kelolaan
        $response2 = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Aset Wakaf Produktif',
            'sifat_barang' => 'S',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisiWakaf->id,
            'cara_perolehan' => '2',
            'status_barang' => '1',
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-08-27',
            'toko_distributor' => 'Wakaf Center',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'umur_ekonomis_tahun' => 5,
        ]);

        $response2->assertRedirect(route('aset.kelolaan'));
        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'Aset Wakaf Produktif',
            'divisi_id' => $divisiWakaf->id,
            'jenis' => 'kelolaan',
        ]);
    }

    public function test_store_aset_otomatis_jenis_tetap_untuk_divisi_direksi(): void
    {
        $kategori = Kategori::first();
        $barang = Barang::first() ?? Barang::create(['kategori_id' => $kategori->id, 'kode_barang' => '98', 'nama_barang' => 'Item Direksi']);
        $divisiDireksi = Divisi::create(['kode_divisi' => '1', 'nama_divisi' => 'Direksi']);

        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop Direksi',
            'sifat_barang' => 'D',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisiDireksi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-08-27',
            'toko_distributor' => 'Vendor IT',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'umur_ekonomis_tahun' => 4,
        ]);

        $response->assertRedirect(route('aset.tetap'));
        $this->assertDatabaseHas('aset', [
            'nama_aset' => 'Laptop Direksi',
            'divisi_id' => $divisiDireksi->id,
            'jenis' => 'tetap',
        ]);
    }

    public function test_update_aset_otomatis_mengubah_jenis_saat_divisi_diubah(): void
    {
        $divisiProgram = Divisi::create(['kode_divisi' => '5', 'nama_divisi' => 'Program']);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $this->aset->id), [
            'nama_aset' => 'Laptop ROG Pindah ke Program',
            'divisi_id' => $divisiProgram->id,
            'kategori_id' => $this->aset->kategori_id,
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 5,
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $this->aset->refresh();
        $this->assertEquals($divisiProgram->id, $this->aset->divisi_id);
        $this->assertEquals('kelolaan', $this->aset->jenis);
    }
}
