<?php

namespace Tests\Feature;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\JurnalAset;
use App\Models\Kategori;
use App\Models\KeuanganAset;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\RiwayatAset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmoduleEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->staff = User::create([
            'name' => 'Staff Editor',
            'email' => 'editor@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Dell']);
        $lokasi = Lokasi::create(['nama_lokasi' => 'Gudang IT', 'kode_lokasi' => 'GD01']);
        $pj = PenanggungJawab::create(['nama' => 'Budi Santoso', 'jabatan' => 'Koordinator']);

        $this->aset = Aset::create([
            'nama_aset' => 'Server Cadangan',
            'kode_aset' => 'ELK-2026-GD01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 166666,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_riwayat_catatan_dapat_diedit_dan_dihapus(): void
    {
        $riwayat = RiwayatAset::create([
            'aset_id' => $this->aset->id,
            'sejak_tanggal' => '2026-02-01',
            'kondisi_persen' => 90,
            'kelengkapan_persen' => 95,
            'jenis_aksi' => 'catatan',
            'keterangan' => 'Pengecekan awal',
            'user_id' => $this->admin->id,
        ]);

        // Edit riwayat
        $response = $this->actingAs($this->staff)->put(route('aset.riwayat.update', $riwayat->id), [
            'sejak_tanggal' => '2026-02-05',
            'kondisi_persen' => 85,
            'kelengkapan_persen' => 90,
            'keterangan' => 'Pengecekan diperbarui',
        ]);

        $response->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'riwayat']));
        $riwayat->refresh();

        $this->assertEquals('2026-02-05', $riwayat->sejak_tanggal->toDateString());
        $this->assertEquals(85, $riwayat->kondisi_persen);
        $this->assertEquals('Pengecekan diperbarui', $riwayat->keterangan);
        $this->assertEquals($this->staff->id, $riwayat->updated_by);

        // Hapus riwayat
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.riwayat.destroy', $riwayat->id));
        $deleteResponse->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'riwayat']));

        $this->assertDatabaseMissing('riwayat_aset', ['id' => $riwayat->id]);
    }

    public function test_riwayat_pembuatan_tidak_dapat_dihapus(): void
    {
        $riwayatPembuatan = RiwayatAset::create([
            'aset_id' => $this->aset->id,
            'sejak_tanggal' => '2026-01-01',
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'pembuatan',
            'keterangan' => 'Aset pertama kali dibuat',
            'user_id' => $this->admin->id,
        ]);

        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.riwayat.destroy', $riwayatPembuatan->id));
        $deleteResponse->assertSessionHas('error');

        $this->assertDatabaseHas('riwayat_aset', ['id' => $riwayatPembuatan->id]);
    }

    public function test_agenda_dapat_diedit_dan_dihapus(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'mingguan',
            'nama_agenda' => 'Pembersihan Debu',
            'hari' => 'senin',
            'biaya_estimasi' => 50000,
            'keterangan' => 'SOP Lama',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Edit agenda
        $response = $this->actingAs($this->staff)->put(route('aset.agenda.update', $agenda->id), [
            'tipe_agenda' => 'bulanan',
            'nama_agenda' => 'Pembersihan Mendalam Server',
            'tanggal_hari' => 15,
            'biaya_estimasi' => '150.000',
            'keterangan' => 'SOP Baru',
        ]);

        $response->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'agenda']));
        $agenda->refresh();

        $this->assertEquals('bulanan', $agenda->tipe_agenda);
        $this->assertEquals('Pembersihan Mendalam Server', $agenda->nama_agenda);
        $this->assertEquals(15, $agenda->tanggal_hari);
        $this->assertEquals(150000, (float) $agenda->biaya_estimasi);
        $this->assertEquals($this->staff->id, $agenda->updated_by);

        // Hapus agenda
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.agenda.destroy', $agenda->id));
        $deleteResponse->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'agenda']));

        $this->assertDatabaseMissing('agenda_aset', ['id' => $agenda->id]);
    }

    public function test_keuangan_dapat_diedit_dan_dihapus(): void
    {
        $keuangan = KeuanganAset::create([
            'aset_id' => $this->aset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => '2026-03-01',
            'nominal' => 200000,
            'jenis_transaksi' => 'Servis Ringan',
            'keterangan' => 'Nota #123',
            'user_id' => $this->admin->id,
        ]);

        // Edit keuangan
        $response = $this->actingAs($this->staff)->put(route('aset.keuangan.update', $keuangan->id), [
            'tanggal' => '2026-03-02',
            'nominal' => '250.000',
            'jenis_transaksi' => 'Servis Lengkap & Ganti Fan',
            'keterangan' => 'Nota #123 (revisi)',
        ]);

        $response->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'keuangan']));
        $keuangan->refresh();

        $this->assertEquals('2026-03-02', $keuangan->tanggal->toDateString());
        $this->assertEquals(250000, (float) $keuangan->nominal);
        $this->assertEquals('Servis Lengkap & Ganti Fan', $keuangan->jenis_transaksi);
        $this->assertEquals($this->staff->id, $keuangan->updated_by);

        // Hapus keuangan
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.keuangan.destroy', $keuangan->id));
        $deleteResponse->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'keuangan']));

        $this->assertDatabaseMissing('keuangan_aset', ['id' => $keuangan->id]);
    }

    public function test_jurnal_manual_dapat_diedit_dan_dihapus(): void
    {
        $jurnal = JurnalAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-04-01',
            'kejadian' => 'Layar monitor berkedip sesekali',
            'tingkat_kerusakan' => 'ringan',
            'status_penanganan' => 'belum_ditangani',
            'user_id' => $this->admin->id,
        ]);

        $this->assertFalse($jurnal->is_dari_agenda);

        // Edit jurnal
        $response = $this->actingAs($this->staff)->put(route('aset.jurnal.update', $jurnal->id), [
            'tanggal' => '2026-04-02',
            'kejadian' => 'Kabel display port diganti baru, masalah berkedip terselesaikan',
        ]);

        $response->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'jurnal']));
        $jurnal->refresh();

        $this->assertEquals('2026-04-02', $jurnal->tanggal->toDateString());
        $this->assertStringContainsString('Kabel display port diganti baru', $jurnal->kejadian);
        $this->assertEquals($this->staff->id, $jurnal->updated_by);

        // Hapus jurnal
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.jurnal.destroy', $jurnal->id));
        $deleteResponse->assertRedirect(route('aset.show', ['aset' => $this->aset->id, 'tab' => 'jurnal']));

        $this->assertDatabaseMissing('jurnal_aset', ['id' => $jurnal->id]);
    }

    public function test_jurnal_otomatis_dari_agenda_terkunci_tidak_bisa_diedit_atau_dihapus(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Servis Tahunan Motherboard',
            'tanggal' => '2026-05-01',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        Storage::fake('public');
        $fakeImg = UploadedFile::fake()->image('servis.jpg');

        // Selesaikan agenda dengan lampiran foto sehingga otomatis masuk ke jurnal
        $this->actingAs($this->admin)->post(route('aset.agenda.selesaikan', $agenda->id), [
            'tanggal_selesai' => '2026-05-01',
            'catatan_penyelesaian' => 'Selesai diservis',
            'biaya_riil' => 0,
            'lampiran' => $fakeImg,
        ]);

        $jurnalOtomatis = JurnalAset::where('agenda_id', $agenda->id)->first();
        $this->assertNotNull($jurnalOtomatis);
        $this->assertTrue($jurnalOtomatis->is_dari_agenda);

        // Coba edit jurnal otomatis -> harus ditolak
        $editResponse = $this->actingAs($this->staff)->put(route('aset.jurnal.update', $jurnalOtomatis->id), [
            'tanggal' => '2026-05-02',
            'kejadian' => 'Mencoba merubah catatan agenda otomatis',
        ]);
        $editResponse->assertSessionHas('error');

        // Pastikan isi jurnal tidak berubah
        $jurnalOtomatis->refresh();
        $this->assertStringContainsString('Penyelesaian Agenda: Servis Tahunan Motherboard', $jurnalOtomatis->kejadian);

        // Coba hapus jurnal otomatis -> harus ditolak
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.jurnal.destroy', $jurnalOtomatis->id));
        $deleteResponse->assertSessionHas('error');

        // Pastikan jurnal masih ada di database
        $this->assertDatabaseHas('jurnal_aset', ['id' => $jurnalOtomatis->id]);
    }

    public function test_keuangan_otomatis_dari_agenda_terkunci_tidak_bisa_dihapus(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Ganti Oli Genset',
            'tanggal' => '2026-06-01',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Selesaikan agenda dengan biaya sehingga membuat keuangan otomatis
        $this->actingAs($this->admin)->post(route('aset.agenda.selesaikan', $agenda->id), [
            'tanggal_selesai' => '2026-06-01',
            'catatan_penyelesaian' => 'Selesai ganti oli',
            'biaya_riil' => '300.000',
        ]);

        $keuanganOtomatis = KeuanganAset::where('agenda_id', $agenda->id)->first();
        $this->assertNotNull($keuanganOtomatis);
        $this->assertTrue($keuanganOtomatis->is_dari_agenda);

        // Coba hapus keuangan otomatis -> harus ditolak
        $deleteResponse = $this->actingAs($this->staff)->delete(route('aset.keuangan.destroy', $keuanganOtomatis->id));
        $deleteResponse->assertSessionHas('error');

        // Pastikan keuangan masih ada di database
        $this->assertDatabaseHas('keuangan_aset', ['id' => $keuanganOtomatis->id]);
    }
}
