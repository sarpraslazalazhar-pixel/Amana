<?php

namespace Tests\Feature;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaSiklusResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin AMANA',
            'email' => 'admin@amana.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Dell']);
        $lokasi = Lokasi::create(['nama_lokasi' => 'Ruang Server', 'kode_lokasi' => 'SRV']);
        $pj = PenanggungJawab::create(['nama' => 'Rian Hidayat', 'jabatan' => 'Admin']);

        $this->aset = Aset::create([
            'nama_aset' => 'Workstation',
            'kode_aset' => 'ELK-2026-SRV-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 333333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_input_biaya_riil_berformat_rupiah_titik_tersimpan_dengan_benar(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Ganti Hardisk',
            'tanggal' => '2026-09-02',
            'biaya_estimasi' => 1500000,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('aset.agenda.selesaikan', $agenda->id), [
            'tanggal_selesai' => '2026-09-02',
            'catatan_penyelesaian' => 'Selesai pasang SSD 1TB',
            'biaya_riil' => '1.500.000', // Format titik Rupiah dari input
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));
        $agenda->refresh();

        $this->assertEquals(1500000, (float) $agenda->biaya_riil);
        $this->assertDatabaseHas('keuangan_aset', [
            'aset_id' => $this->aset->id,
            'nominal' => 1500000,
        ]);
    }

    public function test_agenda_mingguan_otomatis_reset_ke_pending_saat_berganti_minggu(): void
    {
        // Diselesaikan pada 2 minggu yang lalu
        $tanggalSelesaiLalu = Carbon::now()->subWeeks(2)->toDateString();

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'mingguan',
            'nama_agenda' => 'Backup Database Mingguan',
            'hari' => 'senin',
            'status' => 'selesai',
            'tanggal_selesai' => $tanggalSelesaiLalu,
            'user_id' => $this->admin->id,
        ]);

        // Saat di-retrieve ulang dari database, otomatis ter-reset ke pending
        $retrieved = AgendaAset::find($agenda->id);
        $this->assertEquals('pending', $retrieved->status);
    }

    public function test_agenda_bulanan_otomatis_reset_ke_pending_saat_berganti_bulan(): void
    {
        // Diselesaikan bulan lalu
        $tanggalSelesaiBulanLalu = Carbon::now()->subMonths(1)->startOfMonth()->toDateString();

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'bulanan',
            'nama_agenda' => 'Pemeriksaan Suhu & AC Bulanan',
            'tanggal_hari' => 10,
            'status' => 'selesai',
            'tanggal_selesai' => $tanggalSelesaiBulanLalu,
            'user_id' => $this->admin->id,
        ]);

        $retrieved = AgendaAset::find($agenda->id);
        $this->assertEquals('pending', $retrieved->status);
    }

    public function test_agenda_tahunan_otomatis_reset_ke_pending_saat_berganti_tahun(): void
    {
        // Diselesaikan tahun lalu
        $tanggalSelesaiTahunLalu = Carbon::now()->subYears(1)->startOfYear()->toDateString();

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tahunan',
            'nama_agenda' => 'Kalibrasi Unit Tahunan',
            'bulan' => 5,
            'tanggal_hari' => 1,
            'status' => 'selesai',
            'tanggal_selesai' => $tanggalSelesaiTahunLalu,
            'user_id' => $this->admin->id,
        ]);

        $retrieved = AgendaAset::find($agenda->id);
        $this->assertEquals('pending', $retrieved->status);
    }

    public function test_agenda_tanggal_tertentu_selesai_tetap_selesai_dan_disembunyikan_dari_daftar_aktif(): void
    {
        $agendaSelesai = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Upgrade RAM Khusus',
            'tanggal' => '2026-08-01',
            'status' => 'selesai',
            'tanggal_selesai' => '2026-08-01',
            'user_id' => $this->admin->id,
        ]);

        $agendaPending = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Penggantian Power Supply',
            'tanggal' => '2026-09-10',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Status agenda tanggal tertentu tidak di-reset ke pending
        $retrieved = AgendaAset::find($agendaSelesai->id);
        $this->assertEquals('selesai', $retrieved->status);

        // Di halaman detail aset:
        $response = $this->actingAs($this->admin)->get(route('aset.show', $this->aset->id));
        $response->assertOk();

        // Agenda pending tampil
        $response->assertSee('Penggantian Power Supply');
        // Ada bagian toggle 'Lihat Riwayat Agenda Selesai'
        $response->assertSee('Lihat Riwayat Agenda Selesai');
        $response->assertSee('Upgrade RAM Khusus');
    }
}
