<?php

namespace App\Services;

use App\Models\Aset;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsetExportService
{
    /**
     * Bangun query aset berdasarkan filter.
     */
    public static function buildQuery(array $filters = [])
    {
        $query = Aset::with(['kategori', 'barang', 'divisi', 'merk', 'lokasi', 'penanggungJawab']);

        if (! empty($filters['status']) && $filters['status'] !== 'semua') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['klasifikasi']) && $filters['klasifikasi'] !== 'semua') {
            $query->klasifikasi($filters['klasifikasi']);
        }

        if (! empty($filters['divisi_id'])) {
            $query->where('divisi_id', $filters['divisi_id']);
        }

        if (! empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        if (! empty($filters['merk_id'])) {
            $query->where('merk_id', $filters['merk_id']);
        }

        if (! empty($filters['lokasi_id'])) {
            $query->where('lokasi_id', $filters['lokasi_id']);
        }

        if (! empty($filters['penanggung_jawab_id'])) {
            $query->where('penanggung_jawab_id', $filters['penanggung_jawab_id']);
        }

        if (! empty($filters['tgl_dari'])) {
            $query->whereDate('tanggal_pembelian', '>=', $filters['tgl_dari']);
        }

        if (! empty($filters['tgl_sampai'])) {
            $query->whereDate('tanggal_pembelian', '<=', $filters['tgl_sampai']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset_lama', 'LIKE', "%{$search}%")
                    ->orWhere('no_seri', 'LIKE', "%{$search}%")
                    ->orWhere('tipe_model', 'LIKE', "%{$search}%")
                    ->orWhereHas('merk', function ($m) use ($search) {
                        $m->where('nama_merk', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Generate file Spreadsheet (.xlsx) dengan multi-level header & styles.
     */
    public static function createSpreadsheet(array $filters = []): Spreadsheet
    {
        $query = self::buildQuery($filters);
        $items = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Aset');

        // 1. Judul Laporan & Metadata Lembaga
        $klasifikasiLabel = ! empty($filters['klasifikasi']) && $filters['klasifikasi'] !== 'semua'
            ? ' ('.strtoupper($filters['klasifikasi']).')'
            : '';

        $sheet->setCellValue('A1', 'DAFTAR ASET'.$klasifikasiLabel.' - LAZ AL AZHAR');
        $sheet->mergeCells('A1:Z1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('A2', 'Lembaga Amil Zakat Al Azhar — Sistem Manajemen Aset (AMANA)');
        $sheet->mergeCells('A2:Z2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('FF64748B'));

        $sheet->setCellValue('A3', 'Diekspor pada: '.Carbon::now()->translatedFormat('d F Y, H:i').' WIB | Total Data: '.$items->count().' Aset');
        $sheet->mergeCells('A3:Z3');
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->setColor(new Color('FF64748B'));

        // 2. Baris Header Dua Tingkat (Level 1: Baris 5, Level 2: Baris 6)
        // Level 1: Standalone & Group Headers
        $sheet->setCellValue('A5', 'NO');
        $sheet->mergeCells('A5:A6');

        $sheet->setCellValue('B5', 'KODE ASET (BARU)');
        $sheet->mergeCells('B5:B6');

        $sheet->setCellValue('C5', 'KODE ASET LAMA');
        $sheet->mergeCells('C5:C6');

        $sheet->setCellValue('D5', 'IDENTITAS ASET');
        $sheet->mergeCells('D5:I5');

        $sheet->setCellValue('J5', 'PENEMPATAN & PENANGGUNG JAWAB');
        $sheet->mergeCells('J5:M5');

        $sheet->setCellValue('N5', 'PEROLEHAN & PEMBELIAN');
        $sheet->mergeCells('N5:S5');

        $sheet->setCellValue('T5', 'NILAI & PENYUSUTAN');
        $sheet->mergeCells('T5:W5');

        $sheet->setCellValue('X5', 'STATUS & KONDISI');
        $sheet->mergeCells('X5:Z5');

        // Level 2: Sub-headers (Baris 6)
        $subHeaders = [
            'D6' => 'Nama Aset',
            'E6' => 'Sifat',
            'F6' => 'Kategori',
            'G6' => 'Barang',
            'H6' => 'Merk',
            'I6' => 'Tipe/Model',

            'J6' => 'Lokasi',
            'K6' => 'Divisi',
            'L6' => 'Klasifikasi',
            'M6' => 'Penanggung Jawab',

            'N6' => 'Tgl Beli',
            'O6' => 'Toko / Distributor',
            'P6' => 'No Invoice',
            'Q6' => 'Qty',
            'R6' => 'Harga Satuan (Rp)',
            'S6' => 'Harga Total (Rp)',

            'T6' => 'Umur (Thn)',
            'U6' => 'Residu (Rp)',
            'V6' => 'Penyusutan/Bln (Rp)',
            'W6' => 'Nilai Buku Saat Ini (Rp)',

            'X6' => 'Cara Perolehan',
            'Y6' => 'Kondisi Awal',
            'Z6' => 'Status Aset',
        ];

        foreach ($subHeaders as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        // Style Header Level 1 (Dark Emerald/Slate)
        $headerGroupStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D9488']], // Teal-600
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ];
        $sheet->getStyle('A5:Z5')->applyFromArray($headerGroupStyle);

        // Style Header Level 2 (Slate-100)
        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1E293B'], 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']], // Slate-100
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ];
        $sheet->getStyle('A6:Z6')->applyFromArray($subHeaderStyle);

        // 3. Mengisi Data Baris
        $rowNum = 7;
        $no = 1;

        foreach ($items as $aset) {
            $nilaiBuku = PenyusutanCalculator::hitungNilaiBukuSaatIni($aset);

            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValueExplicit('B'.$rowNum, $aset->kode_aset, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$rowNum, $aset->kode_aset_lama ?: '-', DataType::TYPE_STRING);

            // Identitas
            $sheet->setCellValue('D'.$rowNum, $aset->nama_aset);
            $sheet->setCellValue('E'.$rowNum, $aset->sifat_barang === 'D' ? 'Dinamis' : 'Statis');
            $sheet->setCellValue('F'.$rowNum, $aset->kategori->nama_kategori ?? '-');
            $sheet->setCellValue('G'.$rowNum, $aset->barang->nama_barang ?? '-');
            $sheet->setCellValue('H'.$rowNum, $aset->merk->nama_merk ?? '-');
            $sheet->setCellValue('I'.$rowNum, $aset->tipe_model ?: '-');

            // Penempatan
            $sheet->setCellValue('J'.$rowNum, $aset->lokasi->nama_lokasi ?? '-');
            $sheet->setCellValue('K'.$rowNum, $aset->divisi->nama_divisi ?? '-');
            $sheet->setCellValue('L'.$rowNum, $aset->klasifikasi);
            $sheet->setCellValue('M'.$rowNum, $aset->penanggungJawab->nama ?? '-');

            // Pembelian
            $sheet->setCellValue('N'.$rowNum, $aset->tanggal_pembelian ? Carbon::parse($aset->tanggal_pembelian)->format('d/m/Y') : '-');
            $sheet->setCellValue('O'.$rowNum, $aset->toko_distributor ?: '-');
            $sheet->setCellValue('P'.$rowNum, $aset->no_invoice ?: '-');
            $sheet->setCellValue('Q'.$rowNum, $aset->jumlah_unit);
            $sheet->setCellValue('R'.$rowNum, $aset->harga_satuan);
            $sheet->setCellValue('S'.$rowNum, $aset->harga_total);

            // Penyusutan
            $sheet->setCellValue('T'.$rowNum, $aset->umur_ekonomis_tahun);
            $sheet->setCellValue('U'.$rowNum, $aset->nilai_residu);
            $sheet->setCellValue('V'.$rowNum, $aset->penyusutan_per_bulan);
            $sheet->setCellValue('W'.$rowNum, $nilaiBuku);

            // Status
            $sheet->setCellValue('X'.$rowNum, $aset->cara_perolehan === '2' ? 'Hibah / Donasi' : 'Beli');
            $sheet->setCellValue('Y'.$rowNum, $aset->status_barang === '2' ? 'Second' : 'Baru');
            $sheet->setCellValue('Z'.$rowNum, ucfirst($aset->status));

            // Alignment & Number Formatting
            $sheet->getStyle('A'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('N'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('Q'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('T'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('X'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('Y'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('Z'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format Currency
            $sheet->getStyle('R'.$rowNum.':S'.$rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('U'.$rowNum.':W'.$rowNum)->getNumberFormat()->setFormatCode('#,##0');

            // Zebra striping
            if ($no % 2 === 0) {
                $sheet->getStyle('A'.$rowNum.':Z'.$rowNum)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC'); // Slate-50
            }

            $rowNum++;
        }

        // Apply borders ke semua baris data
        if ($rowNum > 7) {
            $dataRange = 'A7:Z'.($rowNum - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB('FFE2E8F0');
        }

        // Auto size kolom
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Download file Excel via HTTP Stream Response.
     */
    public static function download(array $filters = []): StreamedResponse
    {
        $dateStr = date('Ymd_His');
        $filename = "Daftar_Aset_Aktif_{$dateStr}.xlsx";

        if (! empty($filters['klasifikasi']) && $filters['klasifikasi'] !== 'semua') {
            $cleanKlas = str_replace(' ', '_', strtolower($filters['klasifikasi']));
            $filename = "Daftar_Aset_{$cleanKlas}_{$dateStr}.xlsx";
        }

        $spreadsheet = self::createSpreadsheet($filters);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, $headers);
    }
}
