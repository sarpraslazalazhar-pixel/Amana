<?php

namespace Tests\Unit;

use App\Services\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

class ImageOptimizerServiceTest extends TestCase
{
    private ImageOptimizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ImageOptimizerService;
    }

    public function test_mengoptimasi_dan_menyimpan_gambar_ke_format_webp(): void
    {
        // Buat file gambar palsu 1600x1200
        $file = UploadedFile::fake()->image('kamera_hp.jpg', 1600, 1200);

        $savedPath = $this->service->optimizeAndStore($file, 'aset_foto', 1200, 80, 'public');

        // Pastikan path berakhiran .webp dan disimpan di folder aset_foto
        $this->assertStringEndsWith('.webp', $savedPath);
        $this->assertStringStartsWith('aset_foto/', $savedPath);

        // Pastikan file ada di storage
        Storage::disk('public')->assertExists($savedPath);

        // Periksa dimensi gambar yang dihasilkan
        $savedContent = Storage::disk('public')->get($savedPath);
        $resultImage = Image::decode($savedContent);

        // Lebar maksimal 1200, aspek rasio terjaga (1600x1200 -> 1200x900)
        $this->assertLessThanOrEqual(1200, $resultImage->width());
        $this->assertLessThanOrEqual(1200, $resultImage->height());
        $this->assertEquals(1200, $resultImage->width());
        $this->assertEquals(900, $resultImage->height());
    }

    public function test_mendeteksi_file_gambar_dengan_benar(): void
    {
        $imageFile = UploadedFile::fake()->image('foto.png');
        $pdfFile = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $this->assertTrue($this->service->isImage($imageFile));
        $this->assertTrue($this->service->isImage('test.jpg'));
        $this->assertTrue($this->service->isImage('gambar.WEBP'));
        $this->assertFalse($this->service->isImage($pdfFile));
        $this->assertFalse($this->service->isImage('laporan.docx'));
    }

    public function test_store_attachment_smart_mengonversi_gambar_dan_menyimpan_pdf_apa_adanya(): void
    {
        $imageFile = UploadedFile::fake()->image('bukti.png', 800, 600);
        $pdfFile = UploadedFile::fake()->create('invoice.pdf', 150, 'application/pdf');

        $savedImagePath = $this->service->storeAttachmentSmart($imageFile, 'jurnal_lampiran', 'public');
        $savedPdfPath = $this->service->storeAttachmentSmart($pdfFile, 'jurnal_lampiran', 'public');

        // Gambar menjadi .webp
        $this->assertStringEndsWith('.webp', $savedImagePath);
        Storage::disk('public')->assertExists($savedImagePath);

        // PDF tetap .pdf
        $this->assertStringEndsWith('.pdf', $savedPdfPath);
        Storage::disk('public')->assertExists($savedPdfPath);
    }

    public function test_delete_old_image_menghapus_file_dari_storage(): void
    {
        Storage::disk('public')->put('aset_foto/lama.webp', 'dummy content');
        Storage::disk('public')->assertExists('aset_foto/lama.webp');

        $this->service->deleteOldImage('aset_foto/lama.webp', 'public');
        Storage::disk('public')->assertMissing('aset_foto/lama.webp');
    }
}
