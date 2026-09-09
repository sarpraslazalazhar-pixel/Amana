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
        // Bersihkan data bertipe 'pemasukan' dari keuangan_aset
        DB::table('keuangan_aset')->where('tipe', 'pemasukan')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada rollback data yang dihapus
    }
};
