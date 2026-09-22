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
use App\Services\PenyusutanCalculator;
use Carbon\Carbon;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KartuAsetPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    private Aset $sampleAset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Testing',
            'email' => 'admin.pdf@alazhar.or.id',
            'role' => 'super_admin',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Viewer Testing',
            'email' => 'viewer.pdf@alazhar.or.id',
            'role' => 'viewer',
        ]);

        $kategori = Kategori::where('kode_kategori', 'EL')->first() ?? Kategori::create(['kode_kategori' => 'EL', 'nama_kategori' => 'Elektronik']);
        $barang = Barang::where('kode_barang', '14')->first() ?? Barang::create(['kategori_id' => $kategori->id, 'kode_barang' => '14', 'nama_barang' => 'Laptop']);
        $divisi = Divisi::first() ?? Divisi::create(['kode_divisi' => '2', 'nama_divisi' => 'Sekretariat']);
        $lokasi = Lokasi::first() ?? Lokasi::create(['kode_lokasi' => '111', 'nama_lokasi' => 'Lobi Utama', 'gedung' => 'Gedung Pusat']);
        $pj = PenanggungJawab::first() ?? PenanggungJawab::create(['nama' => 'Suryamin', 'kode_pic' => '050', 'divisi_id' => $divisi->id]);
        $merk = Merk::first() ?? Merk::create(['nama_merk' => 'Lenovo']);

        $this->sampleAset = Aset::create([
            'nama_aset' => 'Laptop Lenovo ThinkPad T14 Gen 4',
            'kode_aset' => 'EL14D050212202401',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'merk_id' => $merk->id,
            'tipe_model' => 'ThinkPad T14',
            'produsen' => 'Lenovo Group Ltd',
            'no_seri' => 'SN-TP-99881122',
            'tahun_produksi' => 2024,
            'tanggal_pembelian' => '2024-01-15',
            'toko_distributor' => 'PT Lenovo Indonesia',
            'no_invoice' => 'INV-LNV-2024-001',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'harga_total' => 15000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 250000,
            'deskripsi' => 'Laptop dinamis untuk tim operasional lapangan.',
            'keterangan_tambahan' => 'Dilengkapi tas laptop dan garansi resmi 3 tahun.',
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_super_admin_can_stream_kartu_aset_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.pdf', $this->sampleAset->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_viewer_can_stream_kartu_aset_pdf(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('aset.pdf', $this->sampleAset->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_cannot_export_kartu_aset_pdf(): void
    {
        $response = $this->get(route('aset.pdf', $this->sampleAset->id));

        $response->assertRedirect(route('login'));
    }

    public function test_kartu_aset_pdf_with_download_parameter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.pdf', [
            'aset' => $this->sampleAset->id,
            'download' => '1',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Kartu-Aset-'.$this->sampleAset->kode_aset.'.pdf', $response->headers->get('content-disposition'));
    }

    public function test_kartu_aset_pdf_renders_with_submodules_data(): void
    {
        // Add Riwayat
        RiwayatAset::create([
            'aset_id' => $this->sampleAset->id,
            'sejak_tanggal' => '2024-06-01',
            'penanggung_jawab_id' => $this->sampleAset->penanggung_jawab_id,
            'lokasi_id' => $this->sampleAset->lokasi_id,
            'kondisi_persen' => 95,
            'kelengkapan_persen' => 100,
            'keterangan' => 'Pemeriksaan rutin semester 1',
            'user_id' => $this->admin->id,
        ]);

        // Add Agenda
        AgendaAset::create([
            'aset_id' => $this->sampleAset->id,
            'nama_agenda' => 'Pembersihan thermal & backup data',
            'tipe_agenda' => 'bulanan',
            'tanggal_hari' => 15,
            'biaya_estimasi' => 150000,
            'status' => 'pending',
            'user_id' => $this->admin->id,
        ]);

        // Add Keuangan
        KeuanganAset::create([
            'aset_id' => $this->sampleAset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => '2024-07-10',
            'nominal' => 350000,
            'keterangan' => 'Penggantian pasta pendingin & mouse baru',
            'user_id' => $this->admin->id,
        ]);

        // Add Jurnal
        JurnalAset::create([
            'aset_id' => $this->sampleAset->id,
            'tanggal' => '2024-08-01',
            'kejadian' => 'Upgrade RAM menjadi 32GB untuk kebutuhan komputasi',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.pdf', $this->sampleAset->id));

        $response->assertOk();
    }

    public function test_kartu_aset_pdf_renders_with_photo(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('laptop.jpg', 300, 300);
        $path = $file->store('aset_photos', 'public');

        $this->sampleAset->update(['foto_utama' => $path]);

        $response = $this->actingAs($this->admin)->get(route('aset.pdf', $this->sampleAset->id));

        $response->assertOk();
    }

    public function test_penyusutan_calculator_helpers(): void
    {
        // Test usia aset
        $tglBeli = Carbon::now()->subYears(2)->subMonths(4);
        $usia = PenyusutanCalculator::formatUsiaAset($tglBeli);
        $this->assertEquals('2 Tahun 4 Bulan', $usia);

        $tglBeliBaru = Carbon::now()->subDays(5);
        $usiaBaru = PenyusutanCalculator::formatUsiaAset($tglBeliBaru);
        $this->assertEquals('Kurang dari 1 bulan', $usiaBaru);

        // Test akumulasi penyusutan
        $totalPenyusutan = PenyusutanCalculator::hitungTotalPenyusutanBerjalan($this->sampleAset);
        $this->assertGreaterThanOrEqual(0, $totalPenyusutan);
    }

    public function test_kartu_aset_pdf_table_jurnal_menampilkan_pembuat_jurnal(): void
    {
        JurnalAset::create([
            'aset_id' => $this->sampleAset->id,
            'tanggal' => '2024-08-01',
            'kejadian' => 'Pemasangan SSD NVMe 1TB',
            'user_id' => $this->admin->id,
        ]);

        $view = view('aset.pdf.kartu-aset', [
            'aset' => $this->sampleAset->load(['jurnal.user']),
            'nilaiBuku' => 0,
            'totalPenyusutan' => 0,
            'usiaAset' => '1 tahun',
            'qrBase64' => null,
            'qrTargetUrl' => 'http://example.com',
            'fotoBase64' => null,
            'lembagaName' => 'AL AZHAR',
            'printedAt' => '02 September 2026',
        ])->render();

        $this->assertStringContainsString('Ditambahkan Oleh', $view);
        $this->assertStringNotContainsString('Status / Penanganan', $view);
        $this->assertStringContainsString($this->admin->name, $view);
    }
}
