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
        Schema::create('agenda_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->onDelete('cascade');
            $table->string('tipe_agenda')->default('tanggal_tertentu'); // mingguan, bulanan, tahunan, tanggal_tertentu
            $table->string('nama_agenda');
            $table->string('hari')->nullable(); // senin, selasa, rabu, kamis, jumat, sabtu, minggu
            $table->integer('tanggal_hari')->nullable(); // 1..31
            $table->integer('bulan')->nullable(); // 1..12
            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('biaya_estimasi', 15, 2)->default(0);
            $table->enum('status', ['pending', 'selesai', 'dibatalkan'])->default('pending');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_aset');
    }
};
