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
use App\Services\KodeAsetGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiWilayahFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Lokasi $lokasiCirendeu;

    protected Lokasi $lokasiKlb;

    protected Lokasi $lokasiSawangan;

    protected Lokasi $lokasiSentra;

    protected Aset $asetCirendeu;

    protected Aset $asetSawangan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => 'Elektronik',
            'kode_kategori' => 'EL',
        ]);

        $barang = Barang::create([
            'kategori_id' => $kategori->id,
            'nama_barang' => 'Air Conditioner',
            'kode_barang' => '01',
        ]);

        $divisi = Divisi::create([
            'kode_divisi' => '1',
            'nama_divisi' => 'Sekretariat',
        ]);

        $merk = Merk::create(['nama_merk' => 'Daikin']);

        $pj = PenanggungJawab::create([
            'nama' => 'Ahmad Fikri',
            'divisi_id' => $divisi->id,
            'kode_pic' => '001',
        ]);

        $this->lokasiCirendeu = Lokasi::create([
            'nama_lokasi' => 'Lobi Utama Lt. 1',
            'kode_lokasi' => '111',
            'gedung' => 'Kantor Cirendeu',
        ]);

        $this->lokasiKlb = Lokasi::create([
            'nama_lokasi' => 'Lobi Pelayanan KLB',
            'kode_lokasi' => '210',
            'gedung' => 'KLB Pusat Kebayoran Baru',
        ]);

        $this->lokasiSawangan = Lokasi::create([
            'nama_lokasi' => 'Bengkel Otomotif',
            'kode_lokasi' => '302',
            'gedung' => 'Kampus RGI Sawangan',
        ]);

        $this->lokasiSentra = Lokasi::create([
            'nama_lokasi' => 'RGI Sentra (Gedung Utama)',
            'kode_lokasi' => '400',
            'gedung' => 'RGI Sentra',
        ]);

        $this->asetCirendeu = Aset::create([
            'nama_aset' => 'AC Kantor Cirendeu',
            'kode_aset' => 'EL01S111111202501',
            'sifat_barang' => 'S',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $this->lokasiCirendeu->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Toko Elektronik',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 83333.33,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $this->asetSawangan = Aset::create([
            'nama_aset' => 'AC RGI Sawangan',
            'kode_aset' => 'EL01S302111202502',
            'sifat_barang' => 'S',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $this->lokasiSawangan->id,
            'penanggung_jawab_id' => $pj->id,
            'tanggal_pembelian' => '2025-01-01',
            'toko_distributor' => 'Toko Elektronik',
            'jumlah_unit' => 1,
            'harga_satuan' => 4500000,
            'harga_total' => 4500000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 75000,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_helper_wilayah_pada_model_lokasi(): void
    {
        $options = Lokasi::getWilayahOptions();
        $this->assertArrayHasKey('100-199', $options);
        $this->assertEquals('Kantor Cirendeu', $options['100-199']['nama']);
        $this->assertArrayHasKey('400-499', $options);
        $this->assertEquals('RGI Sentra', $options['400-499']['nama']);

        $this->assertEquals('Kantor Cirendeu', $this->lokasiCirendeu->wilayah_nama);
        $this->assertEquals('100-199', $this->lokasiCirendeu->wilayah_range);

        $this->assertEquals('RGI Sawangan', $this->lokasiSawangan->wilayah_nama);
        $this->assertEquals('300-399', $this->lokasiSawangan->wilayah_range);

        $this->assertEquals('RGI Sentra', $this->lokasiSentra->wilayah_nama);
        $this->assertEquals('400-499', $this->lokasiSentra->wilayah_range);

        $parsed = Lokasi::parseWilayahRange('100-199');
        $this->assertNotNull($parsed);
        $this->assertEquals('1', $parsed['prefix']);
        $this->assertEquals(100, $parsed['min']);
        $this->assertEquals(199, $parsed['max']);

        $parsedCustom = Lokasi::parseWilayahRange('100 - 199');
        $this->assertNotNull($parsedCustom);
        $this->assertEquals(100, $parsedCustom['min']);
    }

    public function test_filter_area_lokasi_pada_daftar_aset_tetap(): void
    {
        // Filter Cirendeu (100-199)
        $response = $this->actingAs($this->admin)->get(route('aset.tetap', ['area_lokasi' => '100-199']));
        $response->assertOk();
        $response->assertSee('AC Kantor Cirendeu');
        $response->assertDontSee('AC RGI Sawangan');

        // Filter Sawangan (300-399)
        $response2 = $this->actingAs($this->admin)->get(route('aset.tetap', ['area_lokasi' => '300-399']));
        $response2->assertOk();
        $response2->assertSee('AC RGI Sawangan');
        $response2->assertDontSee('AC Kantor Cirendeu');
    }

    public function test_filter_search_dengan_range_angka_100_199(): void
    {
        // Search "100-199" langsung di kolom pencarian
        $response = $this->actingAs($this->admin)->get(route('aset.tetap', ['search' => '100-199']));
        $response->assertOk();
        $response->assertSee('AC Kantor Cirendeu');
        $response->assertDontSee('AC RGI Sawangan');

        // Search "300-399" langsung di kolom pencarian
        $response2 = $this->actingAs($this->admin)->get(route('aset.tetap', ['search' => '300-399']));
        $response2->assertOk();
        $response2->assertSee('AC RGI Sawangan');
        $response2->assertDontSee('AC Kantor Cirendeu');
    }

    public function test_filter_area_lokasi_pada_data_master_lokasi(): void
    {
        $response = $this->actingAs($this->admin)->get(route('data.lokasi.index', ['area_lokasi' => '100-199']));
        $response->assertOk();
        $response->assertSee('Lobi Utama Lt. 1');
        $response->assertDontSee('Bengkel Otomotif');
    }

    public function test_kode_aset_generator_parse_mengembalikan_kode_lokasi(): void
    {
        $parsed = KodeAsetGenerator::parse('EL01S111111202501');
        $this->assertNotNull($parsed);
        $this->assertEquals('111', $parsed['kode_lokasi']);
        $this->assertEquals('S', $parsed['sifat_barang']);
    }
}
