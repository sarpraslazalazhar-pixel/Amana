<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('divisi')->updateOrInsert(
            ['kode_divisi' => '7'],
            [
                'nama_divisi' => 'Satuan Audit Internal (SAI)',
                'keterangan' => 'Aset Tetap',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('divisi')->where('kode_divisi', '7')->delete();
    }
};
