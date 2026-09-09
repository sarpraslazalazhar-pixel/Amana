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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aset_id')->nullable()->constrained('aset')->nullOnDelete();
            $table->string('kode_aset')->nullable();
            $table->string('nama_aset')->nullable();
            $table->string('aksi'); // buat_aset, update_data, mutasi, keuangan, agenda, jurnal, ubah_status
            $table->string('model_type')->default('Aset');
            $table->text('deskripsi');
            $table->json('perubahan_data')->nullable(); // format: [{"field": "Nama Aset", "sebelum": "A", "sesudah": "B"}]
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
