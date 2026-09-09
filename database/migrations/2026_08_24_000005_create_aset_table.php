<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aset');
            $table->string('kode_aset', 50)->unique();
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->foreignId('merk_id')->constrained('merk');
            $table->string('tipe_model', 150)->nullable();
            $table->string('produsen', 150)->nullable();
            $table->string('no_seri', 100)->nullable();
            $table->integer('tahun_produksi')->nullable();
            $table->foreignId('lokasi_id')->constrained('lokasi');
            $table->foreignId('penanggung_jawab_id')->constrained('penanggung_jawab');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_pembelian');
            $table->string('toko_distributor');
            $table->string('no_invoice', 100)->nullable();
            $table->integer('jumlah_unit')->default(1);
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('harga_total', 15, 2);
            $table->integer('umur_ekonomis_tahun');
            $table->decimal('penyusutan_per_bulan', 15, 2);
            $table->string('foto_utama', 500)->nullable();
            $table->text('keterangan_tambahan')->nullable();
            $table->enum('status', ['aktif', 'non_aktif'])->default('aktif');
            $table->string('nonaktif_sebab', 255)->nullable();
            $table->text('nonaktif_keterangan')->nullable();
            $table->enum('jenis', ['tetap', 'kelolaan'])->default('tetap');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
