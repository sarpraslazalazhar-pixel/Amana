<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\AuditLog;
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

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    private Aset $sampleAsetTetap;

    private Aset $sampleAsetKelolaan;

    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Super Admin Dashboard',
            'email' => 'admin.dash@alazhar.or.id',
            'role' => 'super_admin',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Viewer Dashboard',
            'email' => 'viewer.dash@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $this->kategori = Kategori::where('kode_kategori', 'EL')->first() ?? Kategori::create(['kode_kategori' => 'EL', 'nama_kategori' => 'Elektronik']);
        $barang = Barang::where('kode_barang', '14')->first() ?? Barang::create(['kategori_id' => $this->kategori->id, 'kode_barang' => '14', 'nama_barang' => 'Laptop']);
        $divisi = Divisi::first() ?? Divisi::create(['kode_divisi' => '2', 'nama_divisi' => 'Kelembagaan']);
        $lokasi = Lokasi::first() ?? Lokasi::create(['kode_lokasi' => '111', 'nama_lokasi' => 'Lobi Utama', 'gedung' => 'Gedung Pusat']);
        $pj = PenanggungJawab::first() ?? PenanggungJawab::create(['nama' => 'H. Rahmat', 'kode_pic' => '050', 'divisi_id' => $divisi->id]);
        $merk = Merk::first() ?? Merk::create(['nama_merk' => 'Lenovo']);

        // Aset Tetap
        $this->sampleAsetTetap = Aset::create([
            'nama_aset' => 'Server Rack Utama',
            'kode_aset' => 'EL14D050212202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'S',
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'merk_id' => $merk->id,
            'tipe_model' => 'PowerEdge R740',
            'tanggal_pembelian' => now()->toDateString(),
            'toko_distributor' => 'Dell Official',
            'jumlah_unit' => 1,
            'harga_satuan' => 50000000,
            'harga_total' => 50000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 833333.33,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // Aset Kelolaan
        $this->sampleAsetKelolaan = Aset::create([
            'nama_aset' => 'Sound System Portable',
            'kode_aset' => 'EL14D050212202602',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'merk_id' => $merk->id,
            'tipe_model' => 'Yamaha StagePas',
            'tanggal_pembelian' => now()->toDateString(),
            'toko_distributor' => 'Yamaha Store',
            'jumlah_unit' => 2,
            'harga_satuan' => 10000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 416666.67,
            'status' => 'aktif',
            'jenis' => 'kelolaan',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_super_admin_and_viewer_can_access_dashboard(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertViewIs('dashboard.index');
        $responseAdmin->assertSee('Dashboard AMANA');
        $responseAdmin->assertSee('apexcharts');

        $responseViewer = $this->actingAs($this->viewer)->get(route('dashboard'));
        $responseViewer->assertOk();
        $responseViewer->assertViewIs('dashboard.index');
    }

    public function test_dashboard_calculates_correct_summary_kpis(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalAktif', 2);
        $response->assertViewHas('totalTetap', 1);
        $response->assertViewHas('totalKelolaan', 1);
        $response->assertViewHas('totalNilaiAwal', 70000000.0);
        $response->assertViewHas('nilaiAwalTetap', 50000000.0);
        $response->assertViewHas('nilaiAwalKelolaan', 20000000.0);
        $response->assertViewHas('totalPenyusutanBulan', 1250000.0);
    }

    public function test_dashboard_provides_chart_kategori_and_penyusutan_data(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('chartKategoriLabels');
        $response->assertViewHas('chartKategoriJumlahTetap');
        $response->assertViewHas('chartKategoriJumlahKelolaan');
        $response->assertViewHas('chartKategoriNilaiTetap');
        $response->assertViewHas('chartKategoriNilaiKelolaan');
        $response->assertViewHas('chartNilaiPenyusutan');

        $chartPenyusutan = $response->viewData('chartNilaiPenyusutan');
        $this->assertEquals(['Aset Tetap', 'Aset Kelolaan'], $chartPenyusutan['labels']);
        $this->assertEquals([50000000, 20000000], $chartPenyusutan['nilai_awal']);
    }

    public function test_dashboard_calculates_mom_growth_percentages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('momGrowth');
        $mom = $response->viewData('momGrowth');
        $this->assertEquals(2, $mom['unit_this_month']);
        $this->assertEquals(70000000.0, $mom['nilai_this_month']);
    }

    public function test_dashboard_loads_five_recent_activity_widgets(): void
    {
        // 1. Riwayat
        RiwayatAset::create([
            'aset_id' => $this->sampleAsetTetap->id,
            'sejak_tanggal' => now()->toDateString(),
            'penanggung_jawab_id' => $this->sampleAsetTetap->penanggung_jawab_id,
            'lokasi_id' => $this->sampleAsetTetap->lokasi_id,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'mutasi',
            'keterangan' => 'Mutasi Server ke Data Center',
            'user_id' => $this->admin->id,
        ]);

        // 2. Keuangan
        KeuanganAset::create([
            'aset_id' => $this->sampleAsetTetap->id,
            'tipe' => 'pengeluaran',
            'tanggal' => now()->toDateString(),
            'nominal' => 1500000,
            'keterangan' => 'Maintenance kabel optik',
            'user_id' => $this->admin->id,
        ]);

        // 3. Jurnal
        JurnalAset::create([
            'aset_id' => $this->sampleAsetTetap->id,
            'tanggal' => now()->toDateString(),
            'kejadian' => 'Pembersihan heatsink rutin',
            'tingkat_kerusakan' => 'ringan',
            'status_penanganan' => 'selesai',
            'user_id' => $this->admin->id,
        ]);

        // 4. Audit Log
        AuditLog::create([
            'user_id' => $this->admin->id,
            'aset_id' => $this->sampleAsetTetap->id,
            'kode_aset' => $this->sampleAsetTetap->kode_aset,
            'nama_aset' => $this->sampleAsetTetap->nama_aset,
            'aksi' => 'tambah_aset',
            'deskripsi' => 'Penambahan server baru',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Server Rack Utama');
        $response->assertSee('Mutasi Server ke Data Center');
        $response->assertSee('Maintenance kabel optik');
        $response->assertSee('Pembersihan heatsink rutin');
        $response->assertSee('Penambahan server baru');
    }
}
