<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\QrConfig;
use App\Models\User;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        // Inisialisasi default QR Config
        foreach (QrConfig::getDefaults() as $key => $meta) {
            QrConfig::create([
                'key' => $key,
                'value' => $meta['value'],
                'group' => $meta['group'],
                'type' => $meta['type'],
                'label' => $meta['label'],
                'urutan' => $meta['urutan'],
            ]);
        }
    }

    private function createSampleAset(string $status = 'aktif'): Aset
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first() ?? Kategori::create(['kode_kategori' => 'EL', 'nama_kategori' => 'Elektronik']);
        $barang = Barang::where('kode_barang', '14')->first() ?? Barang::create(['kategori_id' => $kategori->id, 'kode_barang' => '14', 'nama_barang' => 'Laptop']);
        $divisi = Divisi::first() ?? Divisi::create(['kode_divisi' => '1', 'nama_divisi' => 'Direksi']);
        $lokasi = Lokasi::first() ?? Lokasi::create(['kode_lokasi' => '111', 'nama_lokasi' => 'Lobi']);
        $pj = PenanggungJawab::first() ?? PenanggungJawab::create(['nama' => 'Suryamin', 'kode_pic' => '050']);
        $merk = Merk::create(['nama_merk' => 'Asus']);
        $user = User::factory()->create(['role' => 'super_admin']);

        return Aset::create([
            'nama_aset' => 'Laptop ROG Testing 2026',
            'kode_aset' => 'EL14D05021202601',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'merk_id' => $merk->id,
            'tipe_model' => 'Zephyrus G14',
            'produsen' => 'ASUS Inc',
            'no_seri' => 'SN-99887766',
            'tahun_produksi' => 2025,
            'tanggal_pembelian' => '2026-01-10',
            'toko_distributor' => 'Asus Store Mall',
            'no_invoice' => 'INV-ROG-001',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 416666.67,
            'deskripsi' => 'Laptop multimedia untuk desain',
            'keterangan_tambahan' => 'Garansi 2 tahun resmi',
            'status' => $status,
            'jenis' => 'tetap',
            'created_by' => $user->id,
            'nonaktif_sebab' => $status === 'non_aktif' ? 'Rusak Total Terbakar' : null,
            'nonaktif_keterangan' => $status === 'non_aktif' ? 'Sudah diajukan penghapusan inventaris' : null,
        ]);
    }

    public function test_super_admin_dapat_mengakses_halaman_konfigurasi_qr(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('pengaturan.qr-config.index'));

        $response->assertOk()
            ->assertSeeText('Konfigurasi QR & Portal Scan Publik')
            ->assertSeeText('Tampilan Scan Publik (Smartphone)')
            ->assertSeeText('Keterangan Label Cetak QR')
            ->assertSeeText('Live Simulator Preview');
    }

    public function test_super_admin_dapat_menyimpan_perubahan_konfigurasi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post(route('pengaturan.qr-config.update'), [
            'show_nama_aset' => '1',
            'show_kategori' => '1',
            'show_harga_total' => '1', // diaktifkan
            'show_keuangan' => '1',    // diaktifkan
            'riwayat_mode' => 'semua',
            'label_judul_pilihan' => 'custom',
            'label_judul_custom' => 'YPI AL AZHAR PUSAT',
            'label_baris_1' => 'nama_aset',
            'label_baris_2' => 'lokasi',
        ]);

        $response->assertRedirect(route('pengaturan.qr-config.index'))
            ->assertSessionHas('success');

        $this->assertTrue(QrConfig::getValue('show_harga_total'));
        $this->assertTrue(QrConfig::getValue('show_keuangan'));
        $this->assertEquals('semua', QrConfig::getValue('riwayat_mode'));
        $this->assertEquals('custom', QrConfig::getValue('label_judul_pilihan'));
        $this->assertEquals('YPI AL AZHAR PUSAT', QrConfig::getValue('label_judul_custom'));
        $this->assertEquals('YPI AL AZHAR PUSAT', QrConfig::getEffectiveLabelTitle());
    }

    public function test_reset_konfigurasi_mengembalikan_ke_setelan_bawaan(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // Ubah dulu nilai konfigurasi
        QrConfig::setMany(['show_harga_total' => '1', 'label_judul_preset' => 'TEST CUSTOM']);
        $this->assertTrue(QrConfig::getValue('show_harga_total'));

        // Jalankan reset
        $response = $this->actingAs($admin)->post(route('pengaturan.qr-config.reset'));

        $response->assertRedirect(route('pengaturan.qr-config.index'))
            ->assertSessionHas('success');

        // Pastikan kembali ke default (harga_total = false)
        $this->assertFalse(QrConfig::getValue('show_harga_total'));
    }

    public function test_portal_publik_scan_menyembunyikan_data_keuangan_sesuai_default(): void
    {
        $aset = $this->createSampleAset();

        // Default show_harga_total adalah false
        $this->assertFalse(QrConfig::getValue('show_harga_total'));

        $response = $this->get(route('public.qr', ['kode_aset' => $aset->kode_aset]));

        $response->assertOk()
            ->assertSeeText($aset->nama_aset)
            ->assertSeeText($aset->kode_aset)
            ->assertDontSeeText('Rp 20.000.000'); // Harga total tersembunyi
    }

    public function test_portal_publik_scan_menampilkan_data_keuangan_jika_diaktifkan(): void
    {
        $aset = $this->createSampleAset();

        // Aktifkan harga total
        QrConfig::setMany(['show_harga_total' => '1', 'show_harga_satuan' => '1']);

        $response = $this->get(route('public.qr', ['kode_aset' => $aset->kode_aset]));

        $response->assertOk()
            ->assertSeeText($aset->nama_aset)
            ->assertSeeText('Harga Total Perolehan:')
            ->assertSeeText('Rp 20.000.000');
    }

    public function test_portal_publik_scan_menampilkan_peringatan_non_aktif_jika_aset_nonaktif(): void
    {
        $aset = $this->createSampleAset(status: 'non_aktif');

        $response = $this->get(route('public.qr', ['kode_aset' => $aset->kode_aset]));

        $response->assertOk()
            ->assertSeeText('NON-AKTIF / PURNA PAKAI')
            ->assertSeeText('Informasi Purna Pakai:')
            ->assertSeeText('Rusak Total Terbakar');
    }
}
