<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->unsignedBigInteger('petugas_pengadaan_id')->nullable()->after('penanggung_jawab_id');
            $table->foreign('petugas_pengadaan_id')->references('id')->on('penanggung_jawab')->nullOnDelete();
        });

        Schema::table('aset_import_items', function (Blueprint $table) {
            $table->string('petugas_pengadaan_mentah')->nullable()->after('pj_mentah');
        });
    }

    public function down(): void
    {
        Schema::table('aset_import_items', function (Blueprint $table) {
            $table->dropColumn('petugas_pengadaan_mentah');
        });

        Schema::table('aset', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['petugas_pengadaan_id']);
            }
            $table->dropColumn('petugas_pengadaan_id');
        });
    }
};
