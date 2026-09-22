<?php

namespace Tests\Feature;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\JurnalAset;
use App\Models\Kategori;
use App\Models\KeuanganAset;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\RiwayatAset;
use App\Models\User;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KalenderAsetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    private Aset $sampleAset;

    private Kategori $kategori;

    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Kalender',
            'email' => 'admin.kalender@alazhar.or.id',
            'role' => 'super_admin',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Viewer Kalender',
            'email' => 'viewer.kalender@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $this->kategori = Kategori::where('kode_kategori', 'EL')->first() ?? Kategori::create(['kode_kategori' => 'EL', 'nama_kategori' => 'Elektronik']);
        $barang = Barang::where('kode_barang', '14')->first() ?? Barang::create(['kategori_id' => $this->kategori->id, 'kode_barang' => '14', 'nama_barang' => 'Laptop']);
        $divisi = Divisi::first() ?? Divisi::create(['kode_divisi' => '2', 'nama_divisi' => 'Sekretariat']);
        $this->lokasi = Lokasi::first() ?? Lokasi::create(['kode_lokasi' => '111', 'nama_lokasi' => 'Lobi Utama', 'gedung' => 'Gedung Pusat']);
        $pj = PenanggungJawab::first() ?? PenanggungJawab::create(['nama' => 'Ahmad Suhendar', 'kode_pic' => '050', 'divisi_id' => $divisi->id]);
        $merk = Merk::first() ?? Merk::create(['nama_merk' => 'Lenovo']);

        $this->sampleAset = Aset::create([
            'nama_aset' => 'Laptop ThinkPad X1 Carbon',
            'kode_aset' => 'EL14D050212202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'divisi_id' => $divisi->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'merk_id' => $merk->id,
            'tipe_model' => 'X1 Carbon Gen 10',
            'tanggal_pembelian' => '2026-01-10',
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

    public function test_super_admin_can_view_kalender_aset(): void
    {
        $response = $this->actingAs($this->admin)->get(route('kalender.index'));

        $response->assertOk();
        $response->assertViewIs('kalender.index');
        $response->assertSee('Kalender Aset Terpadu');
        $response->assertSee('Tabel Rekap');
        $response->assertSee('Grid Kalender');
    }

    public function test_viewer_can_view_kalender_aset(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('kalender.index'));

        $response->assertOk();
        $response->assertViewIs('kalender.index');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('kalender.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_kalender_displays_agenda_jurnal_transaksi_riwayat(): void
    {
        $month = 8;
        $year = 2026;

        // Agenda spesifik
        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Servis Fan Cooler',
            'tipe_agenda' => 'tanggal_tertentu',
            'tanggal' => '2026-08-10',
            'biaya_estimasi' => 250000,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Jurnal
        JurnalAset::create([
            'aset_id' => $this->sampleAset->id,
            'tanggal' => '2026-08-15',
            'kejadian' => 'Layar berkedip saat boot',
            'tingkat_kerusakan' => 'ringan',
            'status_penanganan' => 'selesai',
            'user_id' => $this->admin->id,
        ]);

        // Keuangan
        KeuanganAset::create([
            'aset_id' => $this->sampleAset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => '2026-08-18',
            'nominal' => 500000,
            'keterangan' => 'Pembelian charger cadangan',
            'user_id' => $this->admin->id,
        ]);

        // Riwayat
        RiwayatAset::create([
            'aset_id' => $this->sampleAset->id,
            'sejak_tanggal' => '2026-08-20',
            'penanggung_jawab_id' => $this->sampleAset->penanggung_jawab_id,
            'lokasi_id' => $this->sampleAset->lokasi_id,
            'kondisi_persen' => 90,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'mutasi',
            'keterangan' => 'Mutasi penempatan ke ruang IT',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('kalender.index', [
            'month' => $month,
            'year' => $year,
        ]));

        $response->assertOk();
        $response->assertSee('Servis Fan Cooler');
        $response->assertSee('Layar berkedip saat boot');
        $response->assertSee('Pembelian charger cadangan');
        $response->assertSee('Mutasi penempatan ke ruang IT');
    }

    public function test_kalender_expands_weekly_and_monthly_recurring_agenda(): void
    {
        $month = 8;
        $year = 2026;

        // Agenda Mingguan setiap Senin
        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Pembersihan Debu Mingguan',
            'tipe_agenda' => 'mingguan',
            'hari' => 'senin',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Agenda Bulanan setiap tgl 5
        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Audit Fisik Bulanan',
            'tipe_agenda' => 'bulanan',
            'tanggal_hari' => 5,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('kalender.index', [
            'month' => $month,
            'year' => $year,
        ]));

        $response->assertOk();
        $response->assertSee('Pembersihan Debu Mingguan');
        $response->assertSee('Audit Fisik Bulanan');
    }

    public function test_kalender_filter_by_kategori_and_lokasi(): void
    {
        $month = 8;
        $year = 2026;

        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Agenda Khusus Laptop',
            'tipe_agenda' => 'tanggal_tertentu',
            'tanggal' => '2026-08-12',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Filter dengan kategori yang cocok
        $responseMatch = $this->actingAs($this->admin)->get(route('kalender.index', [
            'month' => $month,
            'year' => $year,
            'kategori_id' => $this->kategori->id,
        ]));
        $responseMatch->assertOk();
        $responseMatch->assertSee('Agenda Khusus Laptop');

        // Filter dengan kategori lain yang tidak cocok
        $otherKategori = Kategori::where('id', '!=', $this->kategori->id)->first() ?? Kategori::create(['kode_kategori' => 'ZZ', 'nama_kategori' => 'Kategori Lain']);
        $responseMismatch = $this->actingAs($this->admin)->get(route('kalender.index', [
            'month' => $month,
            'year' => $year,
            'kategori_id' => $otherKategori->id,
        ]));
        $responseMismatch->assertOk();
        $responseMismatch->assertDontSee('Agenda Khusus Laptop');
    }

    public function test_kalender_filter_by_event_types(): void
    {
        $month = 8;
        $year = 2026;

        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Event Agenda Hanya',
            'tipe_agenda' => 'tanggal_tertentu',
            'tanggal' => '2026-08-14',
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        JurnalAset::create([
            'aset_id' => $this->sampleAset->id,
            'tanggal' => '2026-08-14',
            'kejadian' => 'Event Jurnal Hanya',
            'user_id' => $this->admin->id,
        ]);

        // Hanya filter tipe agenda
        $response = $this->actingAs($this->admin)->get(route('kalender.index', [
            'month' => $month,
            'year' => $year,
            'types' => ['agenda'],
        ]));

        $response->assertOk();
        $response->assertSee('Event Agenda Hanya');
        $response->assertDontSee('Event Jurnal Hanya');
    }
}
