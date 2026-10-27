<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lokasi')
            ->where('kode_lokasi', 'LIKE', '4%')
            ->update([
                'gedung' => 'RGI Sentra',
            ]);

        DB::table('lokasi')
            ->where('kode_lokasi', '400')
            ->update([
                'nama_lokasi' => 'RGI Sentra (Gedung Utama)',
            ]);
    }

    public function down(): void
    {
        DB::table('lokasi')
            ->where('kode_lokasi', 'LIKE', '4%')
            ->update([
                'gedung' => 'Dasamas Center',
            ]);

        DB::table('lokasi')
            ->where('kode_lokasi', '400')
            ->update([
                'nama_lokasi' => 'Dasamas Center (Gedung Utama)',
            ]);
    }
};
