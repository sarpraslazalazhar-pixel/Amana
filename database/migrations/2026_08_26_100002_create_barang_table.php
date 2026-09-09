<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
            $table->string('kode_barang', 5);
            $table->string('nama_barang', 150);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['kategori_id', 'kode_barang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
