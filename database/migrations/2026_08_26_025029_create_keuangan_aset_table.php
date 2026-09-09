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
        Schema::create('keuangan_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->onDelete('cascade');
            $table->enum('tipe', ['pengeluaran', 'pemasukan'])->default('pengeluaran');
            $table->date('tanggal');
            $table->string('jenis_transaksi')->nullable(); // service, upgrade, perbaikan, pajak, dll
            $table->decimal('nominal', 15, 2);
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
        Schema::dropIfExists('keuangan_aset');
    }
};
