<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aset_import_items') && ! Schema::hasColumn('aset_import_items', 'kode_sistem_lama')) {
            Schema::table('aset_import_items', function (Blueprint $table) {
                $table->string('kode_sistem_lama', 100)->nullable()->after('kode_aset_lama')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aset_import_items') && Schema::hasColumn('aset_import_items', 'kode_sistem_lama')) {
            Schema::table('aset_import_items', function (Blueprint $table) {
                $table->dropColumn('kode_sistem_lama');
            });
        }
    }
};
