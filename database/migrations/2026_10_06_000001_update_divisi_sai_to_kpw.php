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
        DB::table('divisi')->updateOrInsert(
            ['kode_divisi' => '7'],
            [
                'nama_divisi' => 'Kantor Perwakilan (KPw)',
                'keterangan' => 'Aset',
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('divisi')->where('kode_divisi', '7')->update([
            'nama_divisi' => 'Satuan Audit Internal (SAI)',
            'keterangan' => 'Aset Tetap',
            'updated_at' => now(),
        ]);
    }
};
