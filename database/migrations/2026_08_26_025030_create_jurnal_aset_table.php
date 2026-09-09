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
        Schema::create('jurnal_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->onDelete('cascade');
            $table->date('tanggal');
            $table->text('kejadian');
            $table->string('lampiran')->nullable();
            $table->enum('tingkat_kerusakan', ['ringan', 'sedang', 'berat'])->nullable();
            $table->enum('status_penanganan', ['belum_ditangani', 'dalam_perbaikan', 'selesai'])->default('belum_ditangani');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_aset');
    }
};
