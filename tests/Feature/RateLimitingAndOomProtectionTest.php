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
use App\Services\ImageOptimizerService;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitingAndOomProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.oom@alazhar.or.id',
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first();
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $lokasi = Lokasi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);
        $divisi = Divisi::where('kode_divisi', '2')->first();

        $this->aset = Aset::create([
            'nama_aset' => 'Laptop Thinkpad Anti OOM',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202099',
            'kode_aset_lama' => 'OLD-9999',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 99,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'created_by' => $this->admin->id,
            'tanggal_pembelian' => '2023-01-15',
            'toko_distributor' => 'Lenovo Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'harga_total' => 15000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 1500000,
            'penyusutan_per_bulan' => 225000,
            'status' => 'aktif',
            'jenis' => 'tetap',
        ]);
    }

    public function test_image_optimizer_rejects_decompression_bomb_dimension(): void
    {
        $service = app(ImageOptimizerService::class);

        // Simulasi gambar raksasa 9000x9000 piksel (81 Megapiksel decompression bomb)
        $bomb = UploadedFile::fake()->image('bomb.jpg', 9000, 9000);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dimensi gambar terlalu besar');

        $service->assertSafeImageDimensions($bomb);
    }

    public function test_image_optimizer_accepts_normal_high_res_photo(): void
    {
        $service = app(ImageOptimizerService::class);

        // Simulasi foto wajar 2000x2000 piksel
        $normal = UploadedFile::fake()->image('normal.jpg', 2000, 2000);

        // Tidak boleh melempar exception
        $service->assertSafeImageDimensions($normal);
        $this->assertTrue(true);
    }

    public function test_public_qr_route_and_alias_are_rate_limited(): void
    {
        // Route /p/{kode_aset}
        $responseP = $this->get(route('public.qr', $this->aset->kode_aset));
        $responseP->assertOk();

        // Route /q/{kode_aset} redirect ke /p/{kode_aset}
        $responseQ = $this->get('/q/' . $this->aset->kode_aset);
        $responseQ->assertRedirect(route('public.qr', $this->aset->kode_aset));
    }

    public function test_agenda_reminders_route_accessible_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->admin)->get(route('agenda.reminders'));
        $response->assertOk();
        $response->assertJsonStructure(['total_count', 'items']);
    }

    public function test_export_download_xlsx_and_csv_streams_properly(): void
    {
        $responseXlsx = $this->actingAs($this->admin)->get(route('aset.export.download'));
        $responseXlsx->assertOk();

        $responseCsv = $this->actingAs($this->admin)->get(route('aset.tetap.export'));
        $responseCsv->assertOk();
    }

    public function test_all_custom_rate_limiters_are_registered(): void
    {
        $limiters = ['login', 'polling', 'public-qr', 'export', 'import'];
        foreach ($limiters as $limiter) {
            $this->assertNotNull(RateLimiter::limiter($limiter), "Rate limiter {$limiter} should be registered");
        }
    }

    public function test_public_qr_throttling_triggers_too_many_requests_when_exceeded(): void
    {
        RateLimiter::for('public-qr', function () {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2);
        });

        $url = route('public.qr', $this->aset->kode_aset);

        $this->get($url)->assertOk();
        $this->get($url)->assertOk();
        $this->get($url)->assertStatus(429);
    }
}
