<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->integer('total_baris')->default(0);
            $table->integer('baris_siap')->default(0);
            $table->integer('baris_belum_lengkap')->default(0);
            $table->integer('baris_duplikat')->default(0);
            $table->enum('status', ['draft', 'processing', 'completed', 'failed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('aset_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('aset_import_batches')->cascadeOnDelete();
            $table->integer('baris_ke');
            $table->string('kode_aset_lama', 100)->nullable()->index();
            $table->string('nama_aset_mentah');
            $table->string('kategori_mentah')->nullable();
            $table->string('merk_mentah')->nullable();
            $table->string('tipe_model')->nullable();
            $table->string('produsen')->nullable();
            $table->string('no_seri')->nullable();
            $table->integer('tahun_produksi')->nullable();
            $table->string('lokasi_mentah')->nullable();
            $table->string('pj_mentah')->nullable();
            $table->text('deskripsi_mentah')->nullable();
            $table->date('tanggal_pembelian')->nullable();
            $table->string('toko_distributor')->nullable();
            $table->string('no_invoice')->nullable();
            $table->integer('jumlah_unit')->default(1);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->integer('umur_ekonomis_tahun')->default(5);
            $table->decimal('nilai_residu', 15, 2)->default(0);
            $table->text('keterangan_tambahan')->nullable();

            // 9-Komponen Kode Aset & Master References
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barang')->nullOnDelete();
            $table->enum('sifat_barang', ['D', 'S'])->default('D');
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasi')->nullOnDelete();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('penanggung_jawab')->nullOnDelete();
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->nullOnDelete();
            $table->enum('cara_perolehan', ['1', '2'])->default('1');
            $table->enum('status_barang', ['1', '2'])->default('1');

            // Status & Feedback
            $table->boolean('is_duplicate')->default(false);
            $table->foreignId('existing_aset_id')->nullable()->constrained('aset')->nullOnDelete();
            $table->json('missing_components')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->string('generated_kode_aset', 50)->nullable();
            $table->enum('import_status', ['pending', 'success', 'updated', 'skipped', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_import_items');
        Schema::dropIfExists('aset_import_batches');
    }
};
