<?php

namespace Tests\Unit;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use App\Services\KodeAsetGenerator;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KodeAsetGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
    }

    public function test_generate_kode_aset_dinamis_sesuai_spesifikasi(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first(); // Laptop
        $pic = PenanggungJawab::where('kode_pic', '050')->first(); // Suryamin (050)
        $divisi = Divisi::where('kode_divisi', '2')->first(); // Divisi 2

        $result = KodeAsetGenerator::generate([
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pic->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1', // Beli
            'status_barang' => '2', // Second
            'tanggal_pembelian' => '2015-08-15',
        ]);

        $this->assertEquals('EL14D050212201501', $result['kode_aset']);
        $this->assertEquals(1, $result['nomor_urut']);
        $this->assertEquals('EL', $result['components']['kode_kategori']);
        $this->assertEquals('14', $result['components']['kode_barang']);
        $this->assertEquals('D', $result['components']['sifat_barang']);
        $this->assertEquals('050', $result['components']['kode_keempat']);
        $this->assertEquals('2', $result['components']['kode_divisi']);
        $this->assertEquals('1', $result['components']['cara_perolehan']);
        $this->assertEquals('2', $result['components']['status_barang']);
        $this->assertEquals('2015', $result['components']['tahun']);
        $this->assertEquals('01', $result['components']['nomor_urut']);
    }

    public function test_generate_kode_aset_statis_sesuai_spesifikasi(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '01')->first(); // AC
        $lokasi = Lokasi::where('kode_lokasi', '111')->first(); // Lobi (111)
        $divisi = Divisi::where('kode_divisi', '2')->first(); // Divisi 2

        $result = KodeAsetGenerator::generate([
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'S',
            'lokasi_id' => $lokasi->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1', // Beli
            'status_barang' => '1', // Baru
            'tanggal_pembelian' => '2015-01-10',
        ]);

        $this->assertEquals('EL01S111211201501', $result['kode_aset']);
        $this->assertEquals(1, $result['nomor_urut']);
        $this->assertEquals('S', $result['components']['sifat_barang']);
        $this->assertEquals('111', $result['components']['kode_keempat']);
    }

    public function test_nomor_urut_auto_increment_per_kategori_barang_divisi_tahun(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first();
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $lokasi = Lokasi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Lenovo']);
        $divisi = Divisi::where('kode_divisi', '2')->first();

        // Aset 1
        Aset::create([
            'nama_aset' => 'Laptop Lenovo 1',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050212201501',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '2',
            'nomor_urut' => 1,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'tanggal_pembelian' => '2015-05-01',
            'toko_distributor' => 'Toko A',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 166666.67,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $user->id,
        ]);

        // Generate Aset 2 (kategori, barang, divisi, tahun sama) -> harus 02
        $result = KodeAsetGenerator::generate([
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pic->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '2',
            'tanggal_pembelian' => '2015-09-01',
        ]);

        $this->assertEquals('EL14D050212201502', $result['kode_aset']);
        $this->assertEquals(2, $result['nomor_urut']);
    }

    public function test_parse_kode_aset(): void
    {
        $parsed = KodeAsetGenerator::parse('EL14D050212201520');
        $this->assertNotNull($parsed);
        $this->assertEquals('EL', $parsed['kode_kategori']);
        $this->assertEquals('14', $parsed['kode_barang']);
        $this->assertEquals('D', $parsed['sifat_barang']);
        $this->assertEquals('050', $parsed['kode_keempat']);
        $this->assertEquals('2', $parsed['kode_divisi']);
        $this->assertEquals('1', $parsed['cara_perolehan']);
        $this->assertEquals('2', $parsed['status_barang']);
        $this->assertEquals('2015', $parsed['tahun']);
        $this->assertEquals('20', $parsed['nomor_urut']);
        $this->assertEquals('EL14D050212201520', $parsed['kode_resmi']);
    }

    public function test_parse_flexible_legacy_16_digit_and_15_digit_codes(): void
    {
        // 16-char: EL01S30451120091 (AC Statis, Lokasi 304, Divisi 5 Program, Thn 2009, Urutan 1)
        $p1 = KodeAsetGenerator::parse('EL01S30451120091');
        $this->assertNotNull($p1);
        $this->assertEquals('EL', $p1['kode_kategori']);
        $this->assertEquals('01', $p1['kode_barang']);
        $this->assertEquals('S', $p1['sifat_barang']);
        $this->assertEquals('304', $p1['kode_keempat']);
        $this->assertEquals('5', $p1['kode_divisi']);
        $this->assertEquals('1', $p1['cara_perolehan']);
        $this->assertEquals('1', $p1['status_barang']);
        $this->assertEquals('2009', $p1['tahun']);
        $this->assertEquals('01', $p1['nomor_urut']);
        $this->assertEquals('EL01S304511200901', $p1['kode_resmi']);

        // 16-char: EL01S11121120171 (AC Statis, Lokasi 111, Divisi 2 Kelembagaan, Thn 2017, Urutan 1)
        $p2 = KodeAsetGenerator::parse('EL01S11121120171');
        $this->assertNotNull($p2);
        $this->assertEquals('111', $p2['kode_keempat']);
        $this->assertEquals('2', $p2['kode_divisi']);
        $this->assertEquals('01', $p2['nomor_urut']);

        // 15-char: EL01S1331120161 (AC Statis, Lokasi 133, Divisi 1 Direksi, Thn 2016, Urutan 1)
        $p3 = KodeAsetGenerator::parse('EL01S1331120161');
        $this->assertNotNull($p3);
        $this->assertEquals('133', $p3['kode_keempat']);
        $this->assertEquals('1', $p3['kode_divisi']);
        $this->assertEquals('2016', $p3['tahun']);
        $this->assertEquals('01', $p3['nomor_urut']);
    }
}
