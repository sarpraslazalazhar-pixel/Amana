<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update master Divisi kode 2
        DB::table('divisi')
            ->where('kode_divisi', '2')
            ->update(['nama_divisi' => 'Sekretariat']);

        // 2. Update master Lokasi kode 133
        DB::table('lokasi')
            ->where('kode_lokasi', '133')
            ->update([
                'nama_lokasi' => 'Ruang Kerja Divisi Sekretariat Lt. 3',
                'alamat_lengkap' => 'Jl. Cirendeu Raya No. 1, Ruang Sekretariat Lt. 3, Ciputat Timur, Tangerang Selatan',
            ]);

        // 3. Update jabatan PIC terkait divisi 2
        DB::table('penanggung_jawab')
            ->where('kode_pic', '010')
            ->update(['jabatan' => 'Kepala Divisi Sekretariat']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '044')
            ->update(['jabatan' => 'Staf Legal & Sekretariat']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '147')
            ->update(['jabatan' => 'Staf Hubungan Sekretariat']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '228')
            ->update(['jabatan' => 'Staf Sekretariat & Pengawasan Internal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('divisi')
            ->where('kode_divisi', '2')
            ->update(['nama_divisi' => 'Kelembagaan']);

        DB::table('lokasi')
            ->where('kode_lokasi', '133')
            ->update([
                'nama_lokasi' => 'Ruang Kerja Divisi Kelembagaan Lt. 3',
                'alamat_lengkap' => 'Jl. Cirendeu Raya No. 1, Ruang Kelembagaan Lt. 3, Ciputat Timur, Tangerang Selatan',
            ]);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '010')
            ->update(['jabatan' => 'Kepala Divisi Kelembagaan']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '044')
            ->update(['jabatan' => 'Staf Legal & Kelembagaan']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '147')
            ->update(['jabatan' => 'Staf Hubungan Kelembagaan']);

        DB::table('penanggung_jawab')
            ->where('kode_pic', '228')
            ->update(['jabatan' => 'Staf Kelembagaan & Pengawasan Internal']);
    }
};
