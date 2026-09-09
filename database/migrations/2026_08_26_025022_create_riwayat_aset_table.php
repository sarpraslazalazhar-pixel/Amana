<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->onDelete('cascade');
            $table->date('sejak_tanggal')->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('penanggung_jawab')->nullOnDelete();
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasi')->nullOnDelete();
            $table->integer('jumlah')->default(1);
            $table->integer('kondisi_persen')->default(100);
            $table->integer('kelengkapan_persen')->default(100);
            $table->string('jenis_aksi')->default('mutasi'); // mutasi, pembuatan, update_data, catatan, ubah_status
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_aset');
    }
};
