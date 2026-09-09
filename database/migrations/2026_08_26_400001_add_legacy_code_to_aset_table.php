<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->string('kode_aset_lama', 100)->nullable()->after('kode_aset')->index();
            $table->string('kode_sistem_lama', 100)->nullable()->after('kode_aset_lama');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropIndex(['kode_aset_lama']);
            $table->dropColumn(['kode_aset_lama', 'kode_sistem_lama']);
        });
    }
};
