<?php

namespace Tests\Feature;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use App\Services\AgendaReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Aset $aset;

    protected AgendaReminderService $service;

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
            'nama_aset' => 'Server Core i9',
            'kode_aset' => 'ELK-2026-SRV-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 25000000,
            'harga_total' => 25000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 333333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $this->service = new AgendaReminderService;
    }

    public function test_kalkulasi_due_date_tipe_tanggal_tertentu(): void
    {
        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Ganti Baterai UPS',
            'tanggal' => '2026-09-15',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $due = $this->service->calculateDueDate($agenda);
        $this->assertNotNull($due);
        $this->assertEquals('2026-09-15', $due->toDateString());
    }

    public function test_kalkulasi_due_date_tipe_mingguan(): void
    {
        // 2026-09-03 adalah hari Kamis (Kamis di minggu tersebut)
        // Senin di minggu tersebut adalah 2026-08-31
        // Jumat di minggu tersebut adalah 2026-09-04
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        $agendaJumat = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'mingguan',
            'nama_agenda' => 'Pengecekan Rutin Jumat',
            'hari' => 'jumat',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $due = $this->service->calculateDueDate($agendaJumat);
        $this->assertNotNull($due);
        $this->assertEquals('2026-09-04', $due->toDateString());

        Carbon::setTestNow();
    }

    public function test_kalkulasi_due_date_tipe_bulanan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'bulanan',
            'nama_agenda' => 'Pembersihan Debu Bulanan',
            'tanggal_hari' => 10,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $due = $this->service->calculateDueDate($agenda);
        $this->assertNotNull($due);
        $this->assertEquals('2026-09-10', $due->toDateString());

        Carbon::setTestNow();
    }

    public function test_kalkulasi_due_date_tipe_tahunan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        $agenda = AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tahunan',
            'nama_agenda' => 'Audit Aset Tahunan',
            'bulan' => 9,
            'tanggal_hari' => 20,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $due = $this->service->calculateDueDate($agenda);
        $this->assertNotNull($due);
        $this->assertEquals('2026-09-20', $due->toDateString());

        Carbon::setTestNow();
    }

    public function test_pengelompokan_status_overdue_today_upcoming(): void
    {
        // Tetapkan waktu "sekarang" = 2026-09-03
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        // 1. Overdue (2 hari lalu: 2026-09-01)
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Agenda Lewat',
            'tanggal' => '2026-09-01',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // 2. Today (2026-09-03)
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Agenda Hari Ini',
            'tanggal' => '2026-09-03',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // 3. Upcoming dalam 7 hari (2026-09-06 = H+3)
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Agenda Mendatang',
            'tanggal' => '2026-09-06',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // 4. Jauh di depan (2026-09-25 = > 7 hari, tidak boleh masuk reminder)
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Agenda Masih Lama',
            'tanggal' => '2026-09-25',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // 5. Agenda yang sudah selesai (tidak boleh masuk reminder)
        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Agenda Selesai',
            'tanggal' => '2026-09-01',
            'status' => 'selesai',
            'tanggal_selesai' => '2026-09-01',
            'user_id' => $this->admin->id,
        ]);

        $data = $this->service->getReminderData(forceRefresh: true);

        $this->assertEquals(3, $data['total_count']);
        $this->assertEquals(1, $data['overdue_count']);
        $this->assertEquals(1, $data['today_count']);
        $this->assertEquals(1, $data['upcoming_count']);
        $this->assertTrue($data['has_critical']);
        $this->assertTrue($data['has_reminders']);

        // Item pertama harus yang overdue
        $this->assertEquals('Agenda Lewat', $data['items'][0]['nama_agenda']);
        $this->assertEquals('overdue', $data['items'][0]['category']);
        $this->assertEquals(2, $data['items'][0]['diff_days']);

        // Item kedua hari ini
        $this->assertEquals('Agenda Hari Ini', $data['items'][1]['nama_agenda']);
        $this->assertEquals('today', $data['items'][1]['category']);

        // Item ketiga upcoming
        $this->assertEquals('Agenda Mendatang', $data['items'][2]['nama_agenda']);
        $this->assertEquals('upcoming', $data['items'][2]['category']);

        Carbon::setTestNow();
    }

    public function test_header_bell_dan_dashboard_banner_tampil_saat_ada_reminder(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Servis Fan Pendingin',
            'tanggal' => '2026-09-02', // Terlambat 1 hari
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Cek lonceng notifikasi di header
        $response->assertSee('Pengingat Jadwal Agenda');

        // Cek banner pengingat di dashboard memuat nama agenda dan keterangan terlambat
        $response->assertSee('Perhatian: Ada Jadwal Agenda Perawatan Mendesak');
        $response->assertSee('Servis Fan Pendingin');
        $response->assertSee('1 agenda terlambat');

        Carbon::setTestNow();
    }

    public function test_endpoint_json_agenda_reminders(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        AgendaAset::create([
            'aset_id' => $this->aset->id,
            'tipe_agenda' => 'tanggal_tertentu',
            'nama_agenda' => 'Pembersihan Filter Udara',
            'tanggal' => '2026-09-02',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('agenda.reminders'));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertJsonPath('overdue_count', 1);
        $response->assertJsonPath('items.0.nama_agenda', 'Pembersihan Filter Udara');
        $response->assertJsonPath('items.0.category', 'overdue');

        Carbon::setTestNow();
    }

    public function test_empty_state_tampil_saat_tidak_ada_agenda_mendesak(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3));

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Semua Terjadwal Rapi');
        $response->assertDontSee('Perhatian: Ada Jadwal Agenda Perawatan Mendesak');

        Carbon::setTestNow();
    }
}
