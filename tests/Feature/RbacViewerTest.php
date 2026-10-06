<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use App\Models\User;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $viewer;
    protected Aset $aset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $kategori = Kategori::first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $divisi = Divisi::first();
        $lokasi = Lokasi::first();
        $pj = PenanggungJawab::first();

        $this->aset = Aset::create([
            'nama_aset' => 'Laptop Test RBAC',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL01100111202601',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Toko Komputer',
            'jumlah_unit' => 1,
            'harga_satuan' => 10000000,
            'harga_total' => 10000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'penyusutan_per_bulan' => 208333,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_viewer_dapat_melihat_dashboard_dan_daftar_aset(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->get(route('aset.tetap'))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->get(route('aset.show', $this->aset->id))
            ->assertOk();
    }

    public function test_viewer_dilarang_mengakses_form_tambah_aset(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('aset.create'))
            ->assertForbidden();
    }

    public function test_viewer_dilarang_menambahkan_aset(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('aset.store'), [
                'nama_aset' => 'Aset Ilegal',
            ])
            ->assertForbidden();
    }

    public function test_viewer_dilarang_mengakses_form_edit_dan_mengubah_aset(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('aset.edit', $this->aset->id))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->put(route('aset.update', $this->aset->id), [
                'nama_aset' => 'Nama Baru Ditolak',
            ])
            ->assertForbidden();
    }

    public function test_viewer_dilarang_menghapus_aset(): void
    {
        $this->actingAs($this->viewer)
            ->delete(route('aset.destroy', $this->aset->id))
            ->assertForbidden();
    }

    public function test_viewer_dilarang_mengakses_modul_impor_dan_mutasi(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('aset.import.index'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->post(route('aset.mutasi', $this->aset->id), [])
            ->assertForbidden();
    }

    public function test_viewer_dilarang_mengakses_data_master_dan_pengaturan_qr(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('data.lokasi.index'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('data.kategori.index'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('pengaturan.qr-config.index'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('sistem.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_diizinkan_mengakses_seluruh_modul(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('aset.create'))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('aset.edit', $this->aset->id))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('aset.import.index'))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('pengaturan.qr-config.index'))
            ->assertOk();
    }
}
