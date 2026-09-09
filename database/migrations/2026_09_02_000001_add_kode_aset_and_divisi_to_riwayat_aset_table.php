<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('riwayat_aset', function (Blueprint $table) {
            $table->foreignId('divisi_id')->nullable()->after('lokasi_id')->constrained('divisi')->nullOnDelete();
            $table->string('kode_aset_sebelumnya', 50)->nullable()->after('kelengkapan_persen')->index();
            $table->string('kode_aset_baru', 50)->nullable()->after('kode_aset_sebelumnya');
        });

        // Backfill data pembuatan eksisting dengan kode_aset dan divisi_id aset saat ini
        try {
            $records = DB::table('riwayat_aset')
                ->join('aset', 'riwayat_aset.aset_id', '=', 'aset.id')
                ->select('riwayat_aset.id', 'aset.divisi_id', 'aset.kode_aset')
                ->get();

            foreach ($records as $r) {
                DB::table('riwayat_aset')
                    ->where('id', $r->id)
                    ->update([
                        'divisi_id' => $r->divisi_id,
                        'kode_aset_baru' => $r->kode_aset,
                    ]);
            }
        } catch (Throwable $e) {
            // Log or ignore during migration if tables differ
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_aset', function (Blueprint $table) {
            $table->dropForeign(['divisi_id']);
            $table->dropIndex(['kode_aset_sebelumnya']);
            $table->dropColumn(['divisi_id', 'kode_aset_sebelumnya', 'kode_aset_baru']);
        });
    }
};
