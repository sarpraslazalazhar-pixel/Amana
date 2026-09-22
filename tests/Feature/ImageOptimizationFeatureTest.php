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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

class ImageOptimizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kategori $kategori;

    private Barang $barang;

    private Divisi $divisi;

    private Lokasi $lokasi;

    private PenanggungJawab $pj;

    private Merk $merk;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.image@alazhar.or.id',
            'role' => 'super_admin',
        ]);

        $this->kategori = Kategori::where('kode_kategori', 'EL')->first() ?? Kategori::create(['kode_kategori' => 'EL', 'nama_kategori' => 'Elektronik']);
        $this->barang = Barang::where('kode_barang', '14')->first() ?? Barang::create(['kategori_id' => $this->kategori->id, 'kode_barang' => '14', 'nama_barang' => 'Laptop']);
        $this->divisi = Divisi::first() ?? Divisi::create(['kode_divisi' => '2', 'nama_divisi' => 'Sekretariat']);
        $this->lokasi = Lokasi::first() ?? Lokasi::create(['kode_lokasi' => '111', 'nama_lokasi' => 'Lobi Utama', 'gedung' => 'Pusat']);
        $this->pj = PenanggungJawab::first() ?? PenanggungJawab::create(['nama' => 'Suryamin', 'kode_pic' => '050', 'divisi_id' => $this->divisi->id]);
        $this->merk = Merk::first() ?? Merk::create(['nama_merk' => 'Lenovo']);
    }

    public function test_upload_foto_utama_aset_tersimpan_sebagai_file_webp_teroptimasi(): void
    {
        // Simulasi foto kamera resolusi 2000x1500
        $file = UploadedFile::fake()->image('kamera_kantor.jpg', 2000, 1500);

        $payload = [
            'nama_aset' => 'Laptop Lenovo ThinkPad X1',
            'sifat_barang' => 'D',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Lenovo Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 22000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'foto_utama' => $file,
        ];

        $response = $this->actingAs($this->admin)->post(route('aset.store'), $payload);
        $response->assertRedirect(route('aset.tetap'));

        $aset = Aset::where('nama_aset', 'Laptop Lenovo ThinkPad X1')->first();
        $this->assertNotNull($aset);
        $this->assertNotNull($aset->foto_utama);

        // Ekstensi wajib .webp
        $this->assertStringEndsWith('.webp', $aset->foto_utama);
        Storage::disk('public')->assertExists($aset->foto_utama);

        // Verifikasi dimensi maksimum 1200px
        $savedBinary = Storage::disk('public')->get($aset->foto_utama);
        $decoded = Image::decode($savedBinary);
        $this->assertLessThanOrEqual(1200, $decoded->width());
        $this->assertLessThanOrEqual(1200, $decoded->height());
        $this->assertEquals(1200, $decoded->width());
        $this->assertEquals(900, $decoded->height());
    }

    public function test_update_foto_aset_menghapus_foto_lama_dan_menyimpan_webp_baru(): void
    {
        // Simpan foto awal
        Storage::disk('public')->put('aset_foto/foto_awal.webp', 'konten dummy');

        $aset = Aset::create([
            'nama_aset' => 'Laptop ROG Lama',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202601',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => '01',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Asus Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 333333,
            'foto_utama' => 'aset_foto/foto_awal.webp',
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $newFile = UploadedFile::fake()->image('foto_baru.png', 800, 600);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Laptop ROG Baru',
            'kategori_id' => $this->kategori->id,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Asus Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'jenis' => 'tetap',
            'foto_utama' => $newFile,
        ]);

        $response->assertRedirect(route('aset.show', $aset->id));
        $aset->refresh();

        // Foto lama terhapus
        Storage::disk('public')->assertMissing('aset_foto/foto_awal.webp');

        // Foto baru tersimpan sebagai WebP
        $this->assertStringEndsWith('.webp', $aset->foto_utama);
        Storage::disk('public')->assertExists($aset->foto_utama);
    }

    public function test_artisan_command_amana_optimize_images_mengonversi_gambar_lama(): void
    {
        // Buat file JPEG mentah di disk
        $fakeJpg = UploadedFile::fake()->image('kamera_lama.jpg', 1500, 1000);
        $oldPath = 'aset_foto/file_lama.jpg';
        Storage::disk('public')->put($oldPath, file_get_contents($fakeJpg->getRealPath()));

        $aset = Aset::create([
            'nama_aset' => 'Aset Berfoto Lama',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202699',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => '99',
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 166666,
            'foto_utama' => $oldPath,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        // Jalankan perintah artisan
        $this->artisan('amana:optimize-images')
            ->expectsOutputToContain('AMANA — Optimasi & Konversi Gambar ke WebP')
            ->expectsOutputToContain('File Berhasil Dikonversi : 1')
            ->assertSuccessful();

        $aset->refresh();

        // Path berubah ke .webp
        $this->assertStringEndsWith('.webp', $aset->foto_utama);
        $this->assertNotEquals($oldPath, $aset->foto_utama);

        // File lama terhapus dan file webp baru ada
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($aset->foto_utama);
    }
}
