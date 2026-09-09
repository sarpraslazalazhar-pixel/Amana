<?php

namespace Tests\Unit;

use App\Models\Kategori;
use App\Services\AsetFuzzyMatcher;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsetFuzzyMatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_match_kategori_dari_berbagai_teks(): void
    {
        $el = AsetFuzzyMatcher::matchKategori('EL');
        $this->assertNotNull($el);
        $this->assertEquals('EL', $el->kode_kategori);

        $fn = AsetFuzzyMatcher::matchKategori('Furniture Kantor');
        $this->assertNotNull($fn);
        $this->assertEquals('FN', $fn->kode_kategori);

        $kd = AsetFuzzyMatcher::matchKategori('Kendaraan Operasional');
        $this->assertNotNull($kd);
        $this->assertEquals('KD', $kd->kode_kategori);
    }

    public function test_match_barang_dari_nama_aset(): void
    {
        $katEL = Kategori::where('kode_kategori', 'EL')->first();

        $laptop = AsetFuzzyMatcher::matchBarang('LAPTOP ASUS ROG 15 INCH', $katEL->id);
        $this->assertNotNull($laptop);
        $this->assertEquals('14', $laptop->kode_barang); // Laptop is EL14

        $ac = AsetFuzzyMatcher::matchBarang('AC DAIKIN 1PK RUANG RAPAT', $katEL->id);
        $this->assertNotNull($ac);
        $this->assertEquals('01', $ac->kode_barang); // AC is EL01

        $pc = AsetFuzzyMatcher::matchBarang('KOMPUTER PC CORE I7', $katEL->id);
        $this->assertNotNull($pc);
        $this->assertEquals('11', $pc->kode_barang); // Komputer PC is EL11
    }

    public function test_match_pic_dari_nama_dan_nia(): void
    {
        // Berdasarkan seeder: Suryamin (NIA 050)
        $pj1 = AsetFuzzyMatcher::matchPenanggungJawab('Suryamin');
        $this->assertNotNull($pj1);
        $this->assertEquals('050', $pj1->kode_pic);

        // Pencarian dengan NIA
        $pj2 = AsetFuzzyMatcher::matchPenanggungJawab('Amil NIA 008 (Iwan)');
        $this->assertNotNull($pj2);
        $this->assertEquals('008', $pj2->kode_pic);
    }

    public function test_match_lokasi_dari_teks_dan_kode(): void
    {
        // 111 = Lobi Utama Lt 1
        $lok1 = AsetFuzzyMatcher::matchLokasi('Lantai 1 Lobi Depan');
        $this->assertNotNull($lok1);
        $this->assertEquals('111', $lok1->kode_lokasi);

        // 300 = Kampus RGI Sawangan
        $lok2 = AsetFuzzyMatcher::matchLokasi('Kantor RGI Sawangan Depok');
        $this->assertNotNull($lok2);
        $this->assertEquals('300', $lok2->kode_lokasi);
    }

    public function test_parse_indonesian_date_format(): void
    {
        $d1 = AsetFuzzyMatcher::parseDate('23 Mar 2018');
        $this->assertEquals('2018-03-23', $d1);

        $d2 = AsetFuzzyMatcher::parseDate('01 Jan 1970');
        $this->assertEquals('1970-01-01', $d2);

        $d3 = AsetFuzzyMatcher::parseDate('15 Agustus 2021');
        $this->assertEquals('2021-08-15', $d3);

        $d4 = AsetFuzzyMatcher::parseDate('2024-12-31');
        $this->assertEquals('2024-12-31', $d4);
    }

    public function test_parse_numeric_rupiah(): void
    {
        $n1 = AsetFuzzyMatcher::parseNumeric('Rp 15.000.000');
        $this->assertEquals(15000000.0, $n1);

        $n2 = AsetFuzzyMatcher::parseNumeric('1.250.000,50');
        $this->assertEquals(1250000.5, $n2);

        $n3 = AsetFuzzyMatcher::parseNumeric(750000);
        $this->assertEquals(750000.0, $n3);
    }
}
