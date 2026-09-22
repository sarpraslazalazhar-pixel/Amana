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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerkAsetOpsionalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kategori $kategori;

    protected Barang $barang;

    protected Divisi $divisi;

    protected Lokasi $lokasi;

    protected PenanggungJawab $pj;

    protected Merk $merk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);

        $this->kategori = Kategori::create([
            'nama_kategori' => 'Elektronik & IT',
            'kode_kategori' => 'EL',
        ]);

        $this->barang = Barang::create([
            'nama_barang' => 'Laptop Core i7',
            'kode_barang' => 'LP01',
            'kategori_id' => $this->kategori->id,
            'kode_sub' => '001',
        ]);

        $this->divisi = Divisi::create([
            'nama_divisi' => 'Sekretariat',
            'kode_divisi' => '1',
        ]);

        $this->lokasi = Lokasi::create([
            'nama_lokasi' => 'Kantor Pusat Lt 3',
            'kode_lokasi' => 'PST',
        ]);

        $this->pj = PenanggungJawab::create([
            'nama' => 'Budi Santoso',
            'divisi_id' => $this->divisi->id,
            'kode_pic' => 'PJ01',
        ]);

        $this->merk = Merk::create([
            'nama_merk' => 'Dell',
        ]);
    }

    public function test_form_tambah_aset_menampilkan_label_merk_opsional(): void
    {
        $response = $this->actingAs($this->admin)->get(route('aset.create'));

        $response->assertStatus(200);
        $response->assertSee('Merk Aset');
        $response->assertSee('(Opsional)');
        $response->assertSee('-- Pilih Merk (Opsional) --');
    }

    public function test_tambah_aset_tanpa_merk_berhasil_dan_merk_id_null(): void
    {
        $response = $this->actingAs($this->admin)->post(route('aset.store'), [
            'nama_aset' => 'Laptop Rakitan Custom',
            'sifat_barang' => 'D',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'merk_id' => '', // kosong
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Toko Komputer Mandiri',
            'jumlah_unit' => 1,
            'harga_satuan' => 15000000,
            'umur_ekonomis_tahun' => 4,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $aset = Aset::where('nama_aset', 'Laptop Rakitan Custom')->first();
        $this->assertNotNull($aset);
        $this->assertNull($aset->merk_id);
        $this->assertNull($aset->merk);
    }

    public function test_form_edit_aset_menampilkan_label_merk_opsional(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Laptop Office',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL-2026-PST-0001',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'merk_id' => null,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Toko Komputer Mandiri',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 208333.33,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.edit', $aset->id));

        $response->assertStatus(200);
        $response->assertSee('Merk Aset');
        $response->assertSee('(Opsional)');
        $response->assertSee('-- Pilih Merk (Opsional) --');
    }

    public function test_update_aset_mengosongkan_merk_berhasil(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Laptop Dell Inspiron',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL-2026-PST-0002',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 2,
            'merk_id' => $this->merk->id,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Dell Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 12000000,
            'harga_total' => 12000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 250000,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals($this->merk->id, $aset->merk_id);

        $response = $this->actingAs($this->admin)->put(route('aset.update', $aset->id), [
            'nama_aset' => 'Laptop Dell Inspiron Updated',
            'divisi_id' => $this->divisi->id,
            'kategori_id' => $this->kategori->id,
            'merk_id' => '', // kosongkan merk
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Dell Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 12000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $aset->refresh();
        $this->assertNull($aset->merk_id);
    }

    public function test_detail_aset_tanpa_merk_tampil_tanpa_error(): void
    {
        $aset = Aset::create([
            'nama_aset' => 'Meja Rapat Custom',
            'sifat_barang' => 'S',
            'kode_aset' => 'EL-2026-PST-0003',
            'kategori_id' => $this->kategori->id,
            'barang_id' => $this->barang->id,
            'divisi_id' => $this->divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 3,
            'merk_id' => null,
            'lokasi_id' => $this->lokasi->id,
            'penanggung_jawab_id' => $this->pj->id,
            'tanggal_pembelian' => '2026-03-01',
            'toko_distributor' => 'Pengrajin Kayu',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 83333.33,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('aset.show', $aset->id));
        $response->assertStatus(200);
        $response->assertSee('Meja Rapat Custom');
    }
}
