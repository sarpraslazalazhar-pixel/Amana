<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penanggung_jawab', function (Blueprint $table) {
            $table->string('kode_pic', 5)->nullable()->after('nama');
        });

        Schema::table('aset', function (Blueprint $table) {
            $table->enum('sifat_barang', ['D', 'S'])->default('D')->after('nama_aset');
            $table->foreignId('barang_id')->nullable()->after('kategori_id')->constrained('barang')->nullOnDelete();
            $table->foreignId('divisi_id')->nullable()->after('lokasi_id')->constrained('divisi')->nullOnDelete();
            $table->enum('cara_perolehan', ['1', '2'])->default('1')->after('divisi_id');
            $table->enum('status_barang', ['1', '2'])->default('1')->after('cara_perolehan');
            $table->integer('nomor_urut')->default(1)->after('tahun_produksi');
        });
    }

    public function down(): void
    {
        Schema::table('penanggung_jawab', function (Blueprint $table) {
            $table->dropColumn('kode_pic');
        });

        Schema::table('aset', function (Blueprint $table) {
            $table->dropForeign(['barang_id']);
            $table->dropForeign(['divisi_id']);
            $table->dropColumn([
                'sifat_barang',
                'barang_id',
                'divisi_id',
                'cara_perolehan',
                'status_barang',
                'nomor_urut',
            ]);
        });
    }
};
