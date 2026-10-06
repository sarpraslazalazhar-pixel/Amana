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
        if (Schema::hasTable('keuangan_aset') && ! Schema::hasColumn('keuangan_aset', 'lampiran')) {
            Schema::table('keuangan_aset', function (Blueprint $table) {
                $table->string('lampiran', 500)->nullable()->after('keterangan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('keuangan_aset') && Schema::hasColumn('keuangan_aset', 'lampiran')) {
            Schema::table('keuangan_aset', function (Blueprint $table) {
                $table->dropColumn(['lampiran']);
            });
        }
    }
};
