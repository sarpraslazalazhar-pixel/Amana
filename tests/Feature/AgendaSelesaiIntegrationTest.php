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

class AgendaSelesaiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin AMANA',
            'email' => 'admin@amana.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->staff = User::create([
            'name' => 'Staff Teknisi',
            'email' => 'teknisi@amana.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Lenovo']);
        $lokasi = Lokasi::create(['nama_lokasi' => 'Server Room', 'kode_lokasi' => 'SRV01']);
        $pj = PenanggungJawab::create(['nama' => 'Ahmad Fikri', 'jabatan' => 'IT Support']);

        $this->aset = Aset::create([
            'nama_aset' => 'Server Utama',
            'kode_aset' => 'ELK-2026-SRV01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-10',
            'toko_distributor' => 'Distributor Server',
            'jumlah_unit' => 1,
            'harga_satuan' => 30000000,
            'harga_total' => 30000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 500000,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_agenda_diselesaikan_tanpa_foto_tidak_masuk_ke_jurnal(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Pembersihan Debu Unit Server',
            'tanggal' => '2026-09-05',
            'biaya_estimasi' => 0,
            'keterangan' => 'Pembersihan berkala exhaust fan server',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staff)->post(route('aset.agenda.selesaikan', $agenda->id), [
            'tanggal_selesai' => '2026-09-05',
            'catatan_penyelesaian' => 'Exhaust fan telah dibersihkan menggunakan blower angin.',
            'biaya_riil' => 0,
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $response->assertSessionHas('success');

        // Agenda berstatus selesai
        $agenda->refresh();
        $this->assertEquals('selesai', $agenda->status);
        $this->assertEquals('2026-09-05', $agenda->tanggal_selesai->toDateString());
        $this->assertEquals('Exhaust fan telah dibersihkan menggunakan blower angin.', $agenda->catatan_penyelesaian);
        $this->assertEquals(0, (float) $agenda->biaya_riil);
        $this->assertEquals($this->staff->id, $agenda->updated_by);

        // Tidak masuk ke JurnalAset karena tidak melampirkan foto/file
        $this->assertDatabaseMissing('jurnal_aset', [
            'aset_id' => $this->aset->id,
            'agenda_id' => $agenda->id,
        ]);

        // Tidak ada record di keuangan karena biaya 0
        $this->assertDatabaseMissing('keuangan_aset', [
            'aset_id' => $this->aset->id,
        ]);
    }

    public function test_agenda_dapat_diselesaikan_dengan_biaya_dan_lampiran_otomatis_masuk_jurnal_dan_keuangan(): void
    {
        Storage::fake('public');

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'bulanan',
            'nama_agenda' => 'Penggantian Thermal Paste & Kipas Cadangan',
            'tanggal_hari' => 15,
            'biaya_estimasi' => 500000,
            'keterangan' => 'Rutin setiap bulan',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $fakePdf = UploadedFile::fake()->create('nota_sparepart.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->staff)->post(route('aset.agenda.selesaikan', $agenda->id), [
            'tanggal_selesai' => '2026-09-15',
            'catatan_penyelesaian' => 'Thermal paste diganti dengan merk Arctic MX-4, kipas cadangan dipasang.',
            'biaya_riil' => '450.000', // format bertitik
            'lampiran' => $fakePdf,
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $response->assertSessionHas('success');

        $agenda->refresh();
        $this->assertEquals('selesai', $agenda->status);
        $this->assertEquals(450000, (float) $agenda->biaya_riil);
        $this->assertNotNull($agenda->lampiran_penyelesaian);
        Storage::disk('public')->assertExists($agenda->lampiran_penyelesaian);

        // Record Jurnal terbuat dengan lampiran
        $jurnal = JurnalAset::where('aset_id', $this->aset->id)->first();
        $this->assertNotNull($jurnal);
        $this->assertEquals($agenda->lampiran_penyelesaian, $jurnal->lampiran);
        $this->assertEquals('selesai', $jurnal->status_penanganan);
        $this->assertEquals($this->staff->id, $jurnal->user_id);
        $this->assertEquals('2026-09-15', $jurnal->tanggal->toDateString());

        // Record Keuangan terbuat dengan tipe pengeluaran & nominal 450.000
        $this->assertDatabaseHas('keuangan_aset', [
            'aset_id' => $this->aset->id,
            'tipe' => 'pengeluaran',
            'nominal' => 450000,
            'user_id' => $this->staff->id,
        ]);

        $keuangan = KeuanganAset::where('aset_id', $this->aset->id)->first();
        $this->assertEquals('2026-09-15', $keuangan->tanggal->toDateString());
        $this->assertStringContainsString('Penggantian Thermal Paste', $keuangan->jenis_transaksi);
    }

    public function test_toggle_agenda_yang_sudah_selesai_kembali_ke_pending_mempertahankan_jurnal_dan_keuangan(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'nama_agenda' => 'Servis Berkala AC Server',
            'tipe_agenda' => 'tanggal_tertentu',
            'tanggal' => '2026-09-01',
            'status' => 'selesai',
            'tanggal_selesai' => '2026-09-01',
            'catatan_penyelesaian' => 'Servis beres',
            'biaya_riil' => 200000,
            'user_id' => $this->admin->id,
            'updated_by' => $this->staff->id,
        ]);

        JurnalAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-09-01',
            'kejadian' => 'Penyelesaian Agenda: Servis Berkala AC Server',
            'status_penanganan' => 'selesai',
            'user_id' => $this->staff->id,
        ]);

        KeuanganAset::create([
            'aset_id' => $this->aset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => '2026-09-01',
            'nominal' => 200000,
            'jenis_transaksi' => 'Pemeliharaan / Agenda: Servis Berkala AC Server',
            'user_id' => $this->staff->id,
        ]);

        // Toggle kembali ke pending
        $response = $this->actingAs($this->admin)->patch(route('aset.agenda.toggle', $agenda->id));
        $response->assertSessionHas('success');

        $agenda->refresh();
        $this->assertEquals('pending', $agenda->status);
        $this->assertEquals($this->admin->id, $agenda->updated_by);

        // Jurnal dan Keuangan tetap utuh tidak terhapus
        $this->assertDatabaseCount('jurnal_aset', 1);
        $this->assertDatabaseCount('keuangan_aset', 1);
    }

    public function test_sub_menu_riwayat_agenda_keuangan_jurnal_menampilkan_info_pembuat_dan_pengedit_di_detail_aset(): void
    {
        // 1. Riwayat
        RiwayatAset::create([
            'aset_id' => $this->aset->id,
            'sejak_tanggal' => '2026-08-01',
            'penanggung_jawab_id' => $this->aset->penanggung_jawab_id,
            'lokasi_id' => $this->aset->lokasi_id,
            'jumlah' => 1,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'catatan',
            'keterangan' => 'Pengecekan fisik awal',
            'user_id' => $this->admin->id,
            'updated_by' => $this->staff->id,
        ]);

        // 2. Agenda
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Inspeksi Firewall',
            'tanggal' => '2026-09-20',
            'status' => 'pending',
            'user_id' => $this->admin->id,
            'updated_by' => $this->staff->id,
        ]);

        // 3. Keuangan
        KeuanganAset::create([
            'aset_id' => $this->aset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => '2026-08-15',
            'nominal' => 750000,
            'jenis_transaksi' => 'Beli Kabel LAN Cat 7',
            'user_id' => $this->admin->id,
            'updated_by' => $this->staff->id,
        ]);

        // 4. Jurnal
        JurnalAset::create([
            'aset_id' => $this->aset->id,
            'tanggal' => '2026-08-10',
            'kejadian' => 'Restart darurat karena suhu server naik',
            'status_penanganan' => 'selesai',
            'user_id' => $this->admin->id,
            'updated_by' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $this->aset->id));
        $response->assertOk();

        // Verifikasi nama pembuat dan pengedit ada di HTML halaman detail aset
        $response->assertSee('Super Admin AMANA');
        $response->assertSee('Staff Teknisi');
        $response->assertSee('Ditambahkan:');
        $response->assertSee('Dijadwalkan:');
        $response->assertSee('Dicatat:');
        $response->assertSee('Dilaporkan:');
        $response->assertSee('Terakhir Diedit:');
    }
}
