<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\AsetImportBatch;
use App\Models\AsetImportItem;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\User;
use App\Services\AsetImportService;
use Database\Seeders\MasterKodeAsetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AsetImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterKodeAsetSeeder::class);
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@alazhar.org',
        ]);
    }

    private function createSampleExcelFile(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'A1' => 'Kode Aset',
            'B1' => 'Nama Aset',
            'C1' => 'Kategori',
            'D1' => 'Merk',
            'E1' => 'Penanggung Jawab',
            'F1' => 'Lokasi',
            'G1' => 'Tanggal Pembelian',
            'H1' => 'Jumlah Unit',
            'I1' => 'Harga Satuan',
            'J1' => 'Umur Ekonomis',
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Row 2: Laptop (Dinamis, PIC Suryamin)
        $sheet->setCellValue('A2', 'LAMA-EL-001');
        $sheet->setCellValue('B2', 'Laptop ASUS Core i5');
        $sheet->setCellValue('C2', 'Elektronik');
        $sheet->setCellValue('D2', 'ASUS');
        $sheet->setCellValue('E2', 'Suryamin'); // NIA 050, Divisi 2
        $sheet->setCellValue('F2', 'Kantor Cirendeu');
        $sheet->setCellValue('G2', '15/08/2020');
        $sheet->setCellValue('H2', 1);
        $sheet->setCellValue('I2', 8500000);
        $sheet->setCellValue('J2', 4);

        // Row 3: AC (Statis, Lokasi Lobi)
        $sheet->setCellValue('A3', 'LAMA-EL-002');
        $sheet->setCellValue('B3', 'AC Daikin 1.5 PK');
        $sheet->setCellValue('C3', 'Elektronik');
        $sheet->setCellValue('D3', 'Daikin');
        $sheet->setCellValue('E3', '-');
        $sheet->setCellValue('F3', 'Lobi Utama Lt. 1'); // Kode 111
        $sheet->setCellValue('G3', '10/01/2021');
        $sheet->setCellValue('H3', 2);
        $sheet->setCellValue('I3', 4500000);
        $sheet->setCellValue('J3', 5);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    public function test_upload_and_stage_excel_file(): void
    {
        $filePath = $this->createSampleExcelFile();
        $file = new UploadedFile($filePath, 'daftar_aset_lama.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->admin)->post(route('aset.import.upload'), [
            'file' => $file,
        ]);

        $batch = AsetImportBatch::first();
        $this->assertNotNull($batch);
        $this->assertEquals('daftar_aset_lama.xlsx', $batch->nama_file);
        $this->assertEquals(2, $batch->total_baris);

        $response->assertRedirect(route('aset.import.preview', $batch->id));

        // Check staging items
        $items = $batch->items;
        $this->assertCount(2, $items);

        // Item 1: Laptop Asus -> Auto resolved PIC Suryamin (050), Divisi 2, Sifat D
        $item1 = $items->firstWhere('kode_aset_lama', 'LAMA-EL-001');
        $this->assertNotNull($item1);
        $this->assertEquals('D', $item1->sifat_barang);
        $this->assertNotNull($item1->barang_id);
        $this->assertNotNull($item1->penanggung_jawab_id);
        $this->assertNotNull($item1->divisi_id);
        $this->assertTrue($item1->is_ready);

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function test_bulk_assign_staging_items(): void
    {
        $batch = AsetImportBatch::create([
            'nama_file' => 'test.xlsx',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $item = AsetImportItem::create([
            'batch_id' => $batch->id,
            'baris_ke' => 1,
            'nama_aset_mentah' => 'Kursi Kerja Ergonomis',
            'jumlah_unit' => 5,
            'harga_satuan' => 1200000,
            'umur_ekonomis_tahun' => 5,
            'is_ready' => false,
        ]);

        $divisi = Divisi::where('kode_divisi', '3')->first(); // Fundraising
        $lokasi = Lokasi::where('kode_lokasi', '113')->first(); // Ruang Fundraising

        $response = $this->actingAs($this->admin)->post(route('aset.import.bulk-assign', $batch->id), [
            'item_ids' => [$item->id],
            'divisi_id' => $divisi->id,
            'sifat_barang' => 'S',
            'lokasi_id' => $lokasi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'selection_mode' => 'selected',
        ]);

        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals($divisi->id, $item->divisi_id);
        $this->assertEquals('S', $item->sifat_barang);
        $this->assertEquals($lokasi->id, $item->lokasi_id);
    }

    public function test_commit_staging_batch_creates_assets_with_kode_generator(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first();
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::where('kode_divisi', '2')->first();

        $batch = AsetImportBatch::create([
            'nama_file' => 'migrasi.xlsx',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $item = AsetImportItem::create([
            'batch_id' => $batch->id,
            'baris_ke' => 1,
            'kode_aset_lama' => 'OLD-2020-001',
            'nama_aset_mentah' => 'Laptop Lenovo ThinkPad',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pic->id,
            'lokasi_id' => $lokasi->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2020-06-15',
            'jumlah_unit' => 1,
            'harga_satuan' => 12000000,
            'umur_ekonomis_tahun' => 4,
            'nilai_residu' => 0,
            'is_ready' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('aset.import.commit', $batch->id), [
            'duplicate_action' => 'update',
        ]);

        $response->assertRedirect(route('aset.import.preview', $batch->id));
        $response->assertSessionHas('success');

        // Check created Aset in database
        $aset = Aset::where('kode_aset_lama', 'OLD-2020-001')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('EL14D050211202001', $aset->kode_aset);
        $this->assertEquals(12000000, (float) $aset->harga_total);
        $this->assertEquals(250000, (float) $aset->penyusutan_per_bulan); // 12jt / (4*12) = 250rb
        $this->assertEquals('tetap', $aset->jenis);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);

        // Check Riwayat
        $this->assertDatabaseHas('riwayat_aset', [
            'aset_id' => $aset->id,
            'jenis_aksi' => 'pembuatan',
        ]);
    }

    public function test_duplicate_asset_handling_update_and_skip(): void
    {
        $kategori = Kategori::where('kode_kategori', 'EL')->first();
        $barang = Barang::where('kategori_id', $kategori->id)->where('kode_barang', '14')->first();
        $pic = PenanggungJawab::where('kode_pic', '050')->first();
        $lokasi = Lokasi::first();
        $divisi = Divisi::where('kode_divisi', '2')->first();
        $merk = Merk::firstOrCreate(['nama_merk' => 'Umum']);

        // Existing asset
        $existing = Aset::create([
            'nama_aset' => 'Laptop Lama',
            'sifat_barang' => 'D',
            'kode_aset' => 'EL14D050211202001',
            'kode_aset_lama' => 'DUP-001',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'nomor_urut' => 1,
            'merk_id' => $merk->id,
            'lokasi_id' => $lokasi->id,
            'penanggung_jawab_id' => $pic->id,
            'tanggal_pembelian' => '2020-01-01',
            'toko_distributor' => 'Toko X',
            'jumlah_unit' => 1,
            'harga_satuan' => 5000000,
            'harga_total' => 5000000,
            'umur_ekonomis_tahun' => 5,
            'penyusutan_per_bulan' => 83333.33,
            'status' => 'aktif',
            'jenis' => 'tetap',
            'created_by' => $this->admin->id,
        ]);

        $batch = AsetImportBatch::create([
            'nama_file' => 'duplikat.xlsx',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $item = AsetImportItem::create([
            'batch_id' => $batch->id,
            'baris_ke' => 1,
            'kode_aset_lama' => 'DUP-001',
            'nama_aset_mentah' => 'Laptop Diperbarui',
            'kategori_id' => $kategori->id,
            'barang_id' => $barang->id,
            'sifat_barang' => 'D',
            'penanggung_jawab_id' => $pic->id,
            'lokasi_id' => $lokasi->id,
            'divisi_id' => $divisi->id,
            'cara_perolehan' => '1',
            'status_barang' => '1',
            'tanggal_pembelian' => '2020-01-01',
            'jumlah_unit' => 1,
            'harga_satuan' => 7500000, // new price
            'umur_ekonomis_tahun' => 5,
            'is_duplicate' => true,
            'existing_aset_id' => $existing->id,
            'is_ready' => true,
        ]);

        // Test update action
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(1, $stats['updated']);

        $existing->refresh();
        $this->assertEquals('Laptop Diperbarui', $existing->nama_aset);
        $this->assertEquals(7500000, (float) $existing->harga_satuan);
        $this->assertEquals('EL14D050211202001', $existing->kode_aset); // Keep code unchanged
    }

    public function test_auto_unpack_and_direct_import_17_digit_kode_aset(): void
    {
        // Create an Excel file with exact 17-digit code: EL14D050212201520
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Kode Aset');
        $sheet->setCellValue('B1', 'Nama Aset');
        $sheet->setCellValue('C1', 'Harga Satuan');

        // EL14D050212201520: EL (Kategori), 14 (Laptop), D (Dinamis), 050 (Suryamin), 2 (Divisi 2), 1 (Beli), 2 (Second), 2015 (Thn), 20 (Urutan)
        $sheet->setCellValue('A2', 'EL14D050212201520');
        $sheet->setCellValue('B2', 'Laptop ASUS Suryamin');
        $sheet->setCellValue('C2', 10000000);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_direct_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'data_17digit.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        // Upload and stage
        $batch = AsetImportService::parseAndStage($file, 'data_17digit.xlsx', $this->admin->id);

        $item = $batch->items->first();
        $this->assertNotNull($item);
        $this->assertTrue($item->is_ready, 'Item should be auto-ready because 17-digit code was unpacked');
        $this->assertEquals('D', $item->sifat_barang);
        $this->assertEquals('1', $item->cara_perolehan);
        $this->assertEquals('2', $item->status_barang);
        $this->assertEquals('2015', $item->tanggal_pembelian->format('Y'));

        // Commit batch
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(1, $stats['success']);

        $aset = Aset::where('kode_aset', 'EL14D050212201520')->first();
        $this->assertNotNull($aset, 'Asset should preserve the exact 17-digit code');
        $this->assertEquals(20, $aset->nomor_urut);
        $this->assertEquals('Aset Tetap', $aset->klasifikasi);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_auto_unpack_user_sample_16_digit_and_aliases(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Kode Aset');
        $sheet->setCellValue('B1', 'Nama Aset');
        $sheet->setCellValue('C1', 'Harga Satuan');

        // Row 1: EL01S30451120091 (AC, Statis, Lokasi 304, Divisi 5 Program, Thn 2009, Urutan 1)
        $sheet->setCellValue('A2', 'EL01S30451120091');
        $sheet->setCellValue('B2', 'AC');
        $sheet->setCellValue('C2', 5000000);

        // Row 2: FN01D21031120211 (Brangkas -> Brankas, Furniture, Divisi 3 Fundraising)
        $sheet->setCellValue('A3', 'FN01D21031120211');
        $sheet->setCellValue('B3', 'Brangkas');
        $sheet->setCellValue('C3', 8000000);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_user_sample_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'sample_user.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $batch = AsetImportService::parseAndStage($file, 'sample_user.xlsx', $this->admin->id);

        $item1 = $batch->items->firstWhere('kode_aset_lama', 'EL01S30451120091');
        $this->assertNotNull($item1);
        $this->assertTrue($item1->is_ready, 'Row 1 with 16-digit code should be ready');
        $this->assertEquals('S', $item1->sifat_barang);
        $this->assertNotNull($item1->lokasi_id);
        $this->assertNotNull($item1->divisi_id);

        $item2 = $batch->items->firstWhere('kode_aset_lama', 'FN01D21031120211');
        $this->assertNotNull($item2);
        $this->assertTrue($item2->is_ready, 'Row 2 Brangkas should be matched and ready');
        $this->assertNotNull($item2->barang_id);

        // Commit batch
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(2, $stats['success']);

        // Check normalized asset in database
        $aset1 = Aset::where('kode_aset', 'EL01S304511200901')->first();
        $this->assertNotNull($aset1);
        $this->assertEquals('kelolaan', $aset1->jenis);
        $this->assertEquals('Aset dalam Kelolaan', $aset1->klasifikasi);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_auto_unpack_remaining_legacy_patterns(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Kode Aset');
        $sheet->setCellValue('B1', 'Nama Aset');
        $sheet->setCellValue('C1', 'Harga Satuan');

        // 1. Lokasi 340 (RGI)
        $sheet->setCellValue('A2', 'EL01S34051120093');
        $sheet->setCellValue('B2', 'AC RGI');
        $sheet->setCellValue('C2', 4000000);

        // 2. Lokasi 0111 (Lobi)
        $sheet->setCellValue('A3', 'EL03S011121120241');
        $sheet->setCellValue('B3', 'DISPENSER');
        $sheet->setCellValue('C3', 1500000);

        // 3. Extended Combined format (PIC 069 + Divisi 3 + Lokasi 520)
        $sheet->setCellValue('A4', 'EL21D069352011202521');
        $sheet->setCellValue('B4', 'HP');
        $sheet->setCellValue('C4', 3000000);

        // 4. PIC with leading zero (072 Rudiansah)
        $sheet->setCellValue('A5', 'EL14D07221220253');
        $sheet->setCellValue('B5', 'Laptop');
        $sheet->setCellValue('C5', 7000000);

        // 5. Kendaraan legacy (KD01D0501001220224)
        $sheet->setCellValue('A6', 'KD01D0501001220224');
        $sheet->setCellValue('B6', 'MOBIL');
        $sheet->setCellValue('C6', 150000000);

        // 6. Typo prefix (ELK18 -> EL18)
        $sheet->setCellValue('A7', 'ELK18S113311202401');
        $sheet->setCellValue('B7', 'PRINTER');
        $sheet->setCellValue('C7', 2500000);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_legacy_patterns_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'legacy_patterns.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $batch = AsetImportService::parseAndStage($file, 'legacy_patterns.xlsx', $this->admin->id);

        foreach ($batch->items as $item) {
            $this->assertTrue($item->is_ready, "Row {$item->baris_ke} ({$item->kode_aset_lama}) should be ready");
        }

        // Commit batch
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(6, $stats['success']);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_two_line_header_with_price_and_system_code_import(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1: Group Header (Two-level header)
        $sheet->setCellValue('A1', '');
        $sheet->setCellValue('B1', '');
        $sheet->setCellValue('C1', '');
        $sheet->setCellValue('D1', 'Detil Aset');
        $sheet->setCellValue('E1', 'Detil Aset');
        $sheet->setCellValue('F1', 'Pembelian');
        $sheet->setCellValue('G1', 'Pembelian');
        $sheet->setCellValue('H1', 'Pembelian');
        $sheet->setCellValue('I1', 'Pembelian');
        $sheet->setCellValue('J1', 'Penyusutan');

        // Row 2: Sub-column Headers
        $sheet->setCellValue('A2', 'Kode Aset (lama)');
        $sheet->setCellValue('B2', 'Kode Sistem (lama)');
        $sheet->setCellValue('C2', 'Nama Aset');
        $sheet->setCellValue('D2', 'Kategori');
        $sheet->setCellValue('E2', 'Merk');
        $sheet->setCellValue('F2', 'Tanggal');
        $sheet->setCellValue('G2', 'Jumlah');
        $sheet->setCellValue('H2', 'Harga Satuan (Rp)');
        $sheet->setCellValue('I2', 'Harga Total (Rp)');
        $sheet->setCellValue('J2', 'Umur Ekonomi (Tahun)');

        // Row 3: Data row
        $sheet->setCellValue('A3', 'EL01S30451120091');
        $sheet->setCellValue('B3', '27LGWKC7H7ZEREMQ');
        $sheet->setCellValue('C3', 'AC');
        $sheet->setCellValue('D3', 'Elektronik');
        $sheet->setCellValue('E3', 'Sharp');
        $sheet->setCellValue('F3', '01 Jan 2020');
        $sheet->setCellValue('G3', 1);
        $sheet->setCellValue('H3', 5500000);
        $sheet->setCellValue('I3', 5500000);
        $sheet->setCellValue('J3', 5);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_two_line_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'two_line_header.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $batch = AsetImportService::parseAndStage($file, 'two_line_header.xlsx', $this->admin->id);
        $this->assertEquals(1, $batch->items->count());

        $item = $batch->items->first();
        $this->assertEquals('EL01S30451120091', $item->kode_aset_lama);
        $this->assertEquals('27LGWKC7H7ZEREMQ', $item->kode_sistem_lama);
        $this->assertEquals('AC', $item->nama_aset_mentah);
        $this->assertEquals(5500000, (float) $item->harga_satuan);
        $this->assertTrue($item->is_ready);

        // Commit batch
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(1, $stats['success']);

        $aset = Aset::where('kode_aset_lama', 'EL01S30451120091')->first();
        $this->assertNotNull($aset);
        $this->assertEquals('27LGWKC7H7ZEREMQ', $aset->kode_sistem_lama);
        $this->assertEquals(5500000, (float) $aset->harga_satuan);
        $this->assertEquals(5500000, (float) $aset->harga_total);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_all_sixteen_unresolved_legacy_codes(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Kode Aset');
        $sheet->setCellValue('B1', 'Nama Aset');
        $sheet->setCellValue('C1', 'Harga Satuan');

        $rowsToTest = [
            ['EL55D0302112026', 'HT', 1930100],
            ['EL12S500112021', 'Kulkas', 1500009],
            ['FN02S500112020', 'Kursi Kerja', 350009],
            ['EL14D520693201914', 'Laptop', 3500000],
            ['EL14D23141220263', 'Laptop', 7150000],
            ['EL14D0302122026', 'LAPTOP', 7000000],
            ['EL14D1711122026', 'LAPTOP', 8400000],
            ['EL14D0722122023', 'LAPTOP', 6000009],
            ['EL14S20088611202314', 'Laptop', 3100000],
            ['EL14D200935202514', 'Laptop', 3500000],
            ['EL14D0144122026', 'LAPTOP', 3100000],
            ['FN05S01121120221', 'MEJA RAPAT', 8000000],
            ['FN05S1225222019', 'Meja Rapat', 1000009],
            ['EL54D0302112026', 'Pengeras Suara', 782920],
            ['EL18S5203112019', 'Printer', 1200000],
            ['FN07S1102112020', 'RAK SEPATU', 1500009],
        ];

        foreach ($rowsToTest as $idx => $r) {
            $rowNum = $idx + 2;
            $sheet->setCellValue("A{$rowNum}", $r[0]);
            $sheet->setCellValue("B{$rowNum}", $r[1]);
            $sheet->setCellValue("C{$rowNum}", $r[2]);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_sixteen_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'sixteen_codes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $batch = AsetImportService::parseAndStage($file, 'sixteen_codes.xlsx', $this->admin->id);
        $this->assertEquals(16, $batch->items->count());

        foreach ($batch->items as $item) {
            $this->assertTrue($item->is_ready, "Row {$item->baris_ke} ({$item->kode_aset_lama}) should be ready. Missing: ".implode(', ', $item->missing_components ?? []));
        }

        // Commit batch
        $stats = AsetImportService::commitBatch($batch, 'update', $this->admin->id);
        $this->assertEquals(16, $stats['success']);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
