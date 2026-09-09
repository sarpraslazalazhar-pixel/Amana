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
        // 1. Update agenda_aset: tambah kolom penyelesaian & updated_by
        Schema::table('agenda_aset', function (Blueprint $table) {
            $table->date('tanggal_selesai')->nullable()->after('status');
            $table->text('catatan_penyelesaian')->nullable()->after('tanggal_selesai');
            $table->string('lampiran_penyelesaian')->nullable()->after('catatan_penyelesaian');
            $table->decimal('biaya_riil', 15, 2)->default(0)->after('lampiran_penyelesaian');
            $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        // 2. Update riwayat_aset: tambah kolom updated_by
        Schema::table('riwayat_aset', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        // 3. Update keuangan_aset: tambah kolom updated_by
        Schema::table('keuangan_aset', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        // 4. Update jurnal_aset: tambah kolom updated_by
        Schema::table('jurnal_aset', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_aset', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'tanggal_selesai',
                'catatan_penyelesaian',
                'lampiran_penyelesaian',
                'biaya_riil',
                'updated_by',
            ]);
        });

        Schema::table('riwayat_aset', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['updated_by']);
        });

        Schema::table('keuangan_aset', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['updated_by']);
        });

        Schema::table('jurnal_aset', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['updated_by']);
        });
    }
};
