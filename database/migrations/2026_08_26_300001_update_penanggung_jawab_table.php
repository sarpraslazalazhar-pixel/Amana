<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penanggung_jawab', function (Blueprint $table) {
            $table->foreignId('divisi_id')->nullable()->after('kode_pic')->constrained('divisi')->nullOnDelete();
            $table->string('status', 20)->default('aktif')->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('penanggung_jawab', function (Blueprint $table) {
            $table->dropForeign(['divisi_id']);
            $table->dropColumn(['divisi_id', 'status']);
        });
    }
};
