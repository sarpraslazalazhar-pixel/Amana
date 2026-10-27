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

class RapikanNomorUrutTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $viewer;

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
    }

    public function test_artisan_command_rapikan_urutan(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->first();
        $divisi = Divisi::first();
        $lokasi = Lokasi::first();
        $pj = PenanggungJawab::first();

        // Buat 2 aset urutan terbalik
        $createdBaru = Aset::create([
            'nama_aset' => 'Aset Baru tapi Urut 1',
            'kode_aset' => 'EL01D001111202401',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'tanggal_pembelian' => '2024-05-01',
            'toko_distributor' => 'Toko A',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166,
            'status' => 'aktif',
            'created_by' => $this->superAdmin->id,
        ]);

        $createdLama = Aset::create([
            'nama_aset' => 'Aset Lama tapi Urut 2',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL01D001111201902',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pj->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 2,
            'tanggal_pembelian' => '2019-01-01',
            'toko_distributor' => 'Toko A',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 4,
            'penyusutan_per_bulan' => 104166,
            'status' => 'aktif',
            'created_by' => $this->superAdmin->id,
        ]);

        // Uji dry-run
        $this->artisan('aset:rapikan-urutan', ['--dry-run' => true])
            ->expectsOutputToContain('MODE SIMULASI (DRY-RUN)')
            ->assertSuccessful();

        // Uji eksekusi langsung
        $this->artisan('aset:rapikan-urutan')
            ->expectsOutputToContain('Sukses! Nomor urut dan kode aset berhasil dirapikan.')
            ->assertSuccessful();

        $createdLama->refresh();
        $this->assertEquals(1, $createdLama->nomor_urut);
        $this->assertStringEndsWith('01', $createdLama->kode_aset);

        $createdBaru->refresh();
        $this->assertEquals(2, $createdBaru->nomor_urut);
        $this->assertStringEndsWith('02', $createdBaru->kode_aset);
    }

    public function test_web_route_rapikan_urutan_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('aset.rapikan-urutan'));

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_web_route_rapikan_urutan_viewer_forbidden(): void
    {
        $response = $this->actingAs($this->viewer)
            ->post(route('aset.rapikan-urutan'));

        $response->assertForbidden();
    }
}
