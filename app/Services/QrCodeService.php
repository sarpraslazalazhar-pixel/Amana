<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\QrConfig;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Definisi Template Ukuran Kertas & Grid Stiker
     */
    public static function getTemplates(): array
    {
        return [
            'a4_grid_20' => [
                'key' => 'a4_grid_20',
                'name' => 'Kertas A4 (20 Stiker Kotak)',
                'description' => 'Grid 4 Kolom × 5 Baris. Ukuran label ~4.6 × 5.2 cm. Desain kartu kotak presisi sesuai stiker standar.',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'cols' => 4,
                'rows' => 5,
                'per_page' => 20,
                'label_width' => '46mm',
                'label_height' => '52mm',
                'qr_size' => '25mm',
                'padding' => '2mm',
                'badge' => 'Kotak Standar (Default)',
            ],
            'a4_grid_24' => [
                'key' => 'a4_grid_24',
                'name' => 'Kertas A4 (24 Stiker Kotak)',
                'description' => 'Grid 4 Kolom × 6 Baris. Ukuran label ~4.6 × 4.3 cm. Desain kotak kompak hemat kertas.',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'cols' => 4,
                'rows' => 6,
                'per_page' => 24,
                'label_width' => '46mm',
                'label_height' => '43mm',
                'qr_size' => '21mm',
                'padding' => '1.5mm',
                'badge' => 'Kotak Kompak',
            ],
            'a4_grid_12' => [
                'key' => 'a4_grid_12',
                'name' => 'Kertas A4 (12 Stiker Kotak Besar)',
                'description' => 'Grid 3 Kolom × 4 Baris. Ukuran label ~6.2 × 6.5 cm. Tampilan besar sangat jelas untuk aset utama.',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'cols' => 3,
                'rows' => 4,
                'per_page' => 12,
                'label_width' => '62mm',
                'label_height' => '65mm',
                'qr_size' => '32mm',
                'padding' => '2.5mm',
                'badge' => 'Kotak Besar',
            ],
            'sticker_103' => [
                'key' => 'sticker_103',
                'name' => 'Label Tom & Jerry No. 103',
                'description' => 'Grid 3 Kolom × 4 Baris (12 label per lembar). Ukuran label 6.4 × 3.2 cm presisi untuk kertas TJ 103.',
                'paper_size' => 'custom_103',
                'orientation' => 'portrait',
                'cols' => 3,
                'rows' => 4,
                'per_page' => 12,
                'label_width' => '64mm',
                'label_height' => '32mm',
                'qr_size' => '18mm',
                'padding' => '1.5mm',
                'badge' => 'Tom & Jerry 103',
            ],
            'thermal_single' => [
                'key' => 'thermal_single',
                'name' => 'Stiker Satuan / Thermal Roll',
                'description' => '1 Label per halaman ukuran 5.0 × 6.5 cm. Cocok untuk printer label thermal roll.',
                'paper_size' => 'thermal_50x65',
                'orientation' => 'portrait',
                'cols' => 1,
                'rows' => 1,
                'per_page' => 1,
                'label_width' => '50mm',
                'label_height' => '65mm',
                'qr_size' => '32mm',
                'padding' => '2.5mm',
                'badge' => 'Thermal Roll',
            ],
        ];
    }

    /**
     * Ambil template berdasarkan key
     */
    public static function getTemplate(string $key): array
    {
        $templates = self::getTemplates();

        return $templates[$key] ?? $templates['a4_grid_20'];
    }

    /**
     * Generate QR Code dalam bentuk SVG string
     */
    public static function generateSvg(string $content, int $size = 140): string
    {
        try {
            return (string) QrCode::size($size)
                ->color(15, 23, 42)
                ->backgroundColor(255, 255, 255)
                ->margin(0)
                ->generate($content);
        } catch (\Throwable $e) {
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="w-full h-full"><rect width="100" height="100" fill="#f1f5f9"/><text x="50" y="55" font-size="10" text-anchor="middle" fill="#64748b">QR Code</text></svg>';
        }
    }

    /**
     * Siapkan Data Kartu Label untuk sebuah Aset sesuai QrConfig
     */
    public static function prepareLabelData(Aset $aset, ?array $config = null): array
    {
        if (! $config) {
            $config = QrConfig::getAllConfig();
        }

        $title = QrConfig::getEffectiveLabelTitle();
        $row1Field = $config['qr_label_row1_field'] ?? 'nama_aset';
        $row2Field = $config['qr_label_row2_field'] ?? 'kode_aset';

        $row1Value = self::extractFieldValue($aset, $row1Field);
        $row2Value = self::extractFieldValue($aset, $row2Field);

        $row1Text = mb_substr($row1Value, 0, 25);
        $row2Text = mb_substr($row2Value, 0, 25);

        $scanUrl = route('public.qr', $aset->kode_aset);
        $rawSvg = self::generateSvg($scanUrl, 180);
        $qrBase64 = base64_encode($rawSvg);

        return [
            'aset' => $aset,
            'title' => $title,
            'row1_field' => $row1Field,
            'row1_text' => $row1Text,
            'row2_field' => $row2Field,
            'row2_text' => $row2Text,
            'scan_url' => $scanUrl,
            'qr_svg' => $rawSvg,
            'qr_base64' => $qrBase64,
        ];
    }

    /**
     * Ekstrak nilai field dari model Aset
     */
    public static function extractFieldValue(Aset $aset, string $field): string
    {
        return match ($field) {
            'nama_aset' => $aset->nama_aset ?? '-',
            'kode_aset' => $aset->kode_aset ?? '-',
            'kategori' => $aset->kategori?->nama_kategori ?? '-',
            'lokasi' => $aset->lokasi?->nama_lokasi ?? '-',
            'penanggung_jawab' => $aset->penanggungJawab?->nama ?? '-',
            'merk' => $aset->merk?->nama_merk ?? '-',
            'tipe_model' => $aset->tipe_model ?? '-',
            'no_seri' => $aset->no_seri ?? '-',
            'tahun_produksi' => (string) ($aset->tahun_produksi ?? '-'),
            'tanggal_pembelian' => $aset->tanggal_pembelian ? Carbon::parse($aset->tanggal_pembelian)->format('d/m/Y') : '-',
            default => (string) ($aset->{$field} ?? '-'),
        };
    }
}
