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
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisiSatuanAuditInternalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->admin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin.sai@test.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
        ]);
    }

    public function test_master_divisi_satuan_audit_internal_tersedia_dengan_keterangan_aset_tetap(): void
    {
        $divisi = Divisi::where('kode_divisi', '7')->first();

        $this->assertNotNull($divisi);
        $this->assertEquals('Satuan Audit Internal (SAI)', $divisi->nama_divisi);
        $this->assertEquals('Aset Tetap', $divisi->keterangan);
    }

    public function test_generate_kode_aset_dengan_divisi_sai_menghasilkan_digit_tujuh(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $pj = PenanggungJawab::first();
        $divisi = Divisi::where('kode_divisi', '7')->first();

        $result = KodeAsetGenerator::generate([
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pj->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-23',
        ]);

        $this->assertEquals('7', $result['components']['kode_divisi']);
        // Kode Aset format: Kategori(2) + Barang(2) + Sifat(1) + PIC(3) + Divisi(1) + Cara(1) + Status(1) + Tahun(4) + Urutan(2)
        // Karakter ke-9 (index 8) adalah Divisi
        $this->assertEquals('7', substr($result['kode_aset'], 8, 1));
    }

    public function test_aset_baru_dengan_divisi_sai_otomatis_berjenis_tetap(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $pj = PenanggungJawab::first();
        $lokasi = Lokasi::first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Dell']);
        $divisi = Divisi::where('kode_divisi', '7')->first();

        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop Auditor SAI',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pj->id,
            'lokasi_id' => $lokasi->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-09-23',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 4,
            'merk_id' => $merk->id,
        ]);

        $response->assertRedirect();

        $aset = Aset::where('nama_aset', 'Laptop Auditor SAI')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals($divisi->id, $aset->divisi_id);
        $this->assertEquals('7', substr($aset->kode_aset, 8, 1));
    }

    public function test_form_create_dan_edit_menampilkan_pilihan_divisi_sai(): void
    {
        $responseCreate = $this->actingAs($this->admin)->get(route('aset.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Kode 7 — Satuan Audit Internal (SAI)');

        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $pj = PenanggungJawab::first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::where('kode_divisi', '7')->first();

        $aset = Aset::create([
            'nama_aset' => 'PC Server Audit SAI',
            'kode_aset' => 'EL11D001711202601',
            'jenis' => 'tetap',
            'status' => 'aktif',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pj->id,
            'lokasi_id' => $lokasi->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 333333.33,
            'tanggal_pembelian' => '2026-09-23',
        ]);

        $responseEdit = $this->actingAs($this->admin)->get(route('aset.edit', $aset->id));
        $responseEdit->assertStatus(200);
        $responseEdit->assertSee('Kode 7 — Satuan Audit Internal (SAI)');
    }
}
