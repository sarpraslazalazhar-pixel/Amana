<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\AuditLog;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewer;

    protected Aset $aset;

    protected Lokasi $lokasi1;

    protected Lokasi $lokasi2;

    protected PenanggungJawab $pj1;

    protected PenanggungJawab $pj2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->viewer = User::create([
            'name' => 'Viewer Test',
            'email' => 'viewer@test.com',
            'password' => bcrypt('password'),
            'role' => 'viewer',
        ]);

        $kategori = Kategori::create(['nama_kategori' => 'Elektronik', 'kode_kategori' => 'ELK']);
        $merk = Merk::create(['nama_merk' => 'Lenovo']);
        $this->lokasi1 = Lokasi::create(['nama_lokasi' => 'Ruang IT', 'kode_lokasi' => 'IT01']);
        $this->lokasi2 = Lokasi::create(['nama_lokasi' => 'Ruang Direksi', 'kode_lokasi' => 'DIR01']);
        $this->pj1 = PenanggungJawab::create(['nama' => 'Ahmad', 'jabatan' => 'Staff IT']);
        $this->pj2 = PenanggungJawab::create(['nama' => 'Budi', 'jabatan' => 'Manajer']);

        $this->aset = Aset::create([
            'nama_aset' => 'ThinkPad X1 Carbon',
            'kode_aset' => 'ELK-2026-IT01-0001',
            'kategori_id' => $kategori->id,
            'merk_id' => $merk->id,
            'lokasi_id' => $this->lokasi1->id,
            'penanggung_jawab_id' => $this->pj1->id,
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Lenovo Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'harga_total' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 333333,
            'nilai_residu' => 0,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_viewer_cannot_access_audit_log_page(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('audit-log.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_audit_log_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('audit-log.index'));
        $response->assertOk();
        $response->assertSeeText('Log Aktivitas & Audit');
    }

    public function test_audit_log_records_diff_when_asset_data_is_updated(): void
    {
        $response = $this->actingAs($this->admin)->put(route('aset.update', $this->aset->id), [
            'nama_aset' => 'ThinkPad X1 Carbon Gen 10 (Updated)',
            'kategori_id' => $this->aset->kategori_id,
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi1->id, // Lokasi tetap sama
            'penanggung_jawab_id' => $this->pj1->id, // PJ tetap sama
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Lenovo Official Store',
            'jumlah_unit' => 2,
            'harga_satuan' => 22000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 1000000,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));

        // Audit Log harus mencatat perubahannya
        $this->assertDatabaseHas('audit_logs', [
            'aset_id' => $this->aset->id,
            'aksi' => 'update_data',
        ]);

        $log = AuditLog::where('aset_id', $this->aset->id)->where('aksi', 'update_data')->first();
        $this->assertNotNull($log);
        $this->assertIsArray($log->perubahan_data);

        // Pastikan riwayat_aset fisik TIDAK tercemar karena tidak ada mutasi PJ / Lokasi
        $this->assertEquals(0, $this->aset->riwayat()->where('jenis_aksi', 'mutasi')->count());
    }

    public function test_riwayat_aset_records_mutation_when_location_or_pj_changes(): void
    {
        $response = $this->actingAs($this->admin)->put(route('aset.update', $this->aset->id), [
            'nama_aset' => $this->aset->nama_aset,
            'kategori_id' => $this->aset->kategori_id,
            'merk_id' => $this->aset->merk_id,
            'lokasi_id' => $this->lokasi2->id, // Lokasi pindah
            'penanggung_jawab_id' => $this->pj2->id, // PJ pindah
            'tanggal_pembelian' => '2026-01-01',
            'toko_distributor' => 'Lenovo Official Store',
            'jumlah_unit' => 1,
            'harga_satuan' => 20000000,
            'umur_ekonomis_tahun' => 5,
            'nilai_residu' => 0,
            'jenis' => 'tetap',
        ]);

        $response->assertRedirect(route('aset.show', $this->aset->id));

        // Harus ada mutasi di riwayat_aset
        $this->assertDatabaseHas('riwayat_aset', [
            'aset_id' => $this->aset->id,
            'penanggung_jawab_id' => $this->pj2->id,
            'lokasi_id' => $this->lokasi2->id,
            'jenis_aksi' => 'mutasi',
        ]);

        // Dan tercatat juga di audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'aset_id' => $this->aset->id,
            'aksi' => 'update_data',
        ]);
    }

    public function test_audit_log_filters(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'aset_id' => $this->aset->id,
            'kode_aset' => $this->aset->kode_aset,
            'nama_aset' => $this->aset->nama_aset,
            'aksi' => 'keuangan',
            'deskripsi' => 'Transaksi pengeluaran pembelian mouse',
        ]);

        $response = $this->actingAs($this->admin)->get(route('audit-log.index', ['aksi' => 'keuangan']));
        $response->assertOk();
        $response->assertSeeText('Transaksi pengeluaran pembelian mouse');

        $responseEmpty = $this->actingAs($this->admin)->get(route('audit-log.index', ['aksi' => 'agenda']));
        $responseEmpty->assertOk();
        $responseEmpty->assertDontSeeText('Transaksi pengeluaran pembelian mouse');
    }
}
