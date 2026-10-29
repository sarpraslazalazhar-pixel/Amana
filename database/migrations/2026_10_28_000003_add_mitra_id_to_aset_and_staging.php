<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom mitra_id nullable + foreign key
        Schema::table('aset', function (Blueprint $table) {
            $table->unsignedBigInteger('mitra_id')->nullable()->after('toko_distributor');
            $table->foreign('mitra_id')->references('id')->on('mitra')->nullOnDelete();
        });

        // 2. Migrasi data lama dari toko_distributor -> buat record Mitra unik -> isi FK
        $existingRows = DB::table('aset')
            ->select('id', 'toko_distributor')
            ->whereNotNull('toko_distributor')
            ->where('toko_distributor', '!=', '')
            ->get();

        $slugToMitraId = [];
        foreach ($existingRows as $row) {
            $raw = trim((string) $row->toko_distributor);
            if ($raw === '') {
                continue;
            }
            $slug = strtolower($raw);
            if (! isset($slugToMitraId[$slug])) {
                // Cek apakah sudah ada di tabel mitra (misal seeded atau created)
                $existingMitra = DB::table('mitra')
                    ->whereRaw('LOWER(nama) = ?', [$slug])
                    ->first();

                if ($existingMitra) {
                    $slugToMitraId[$slug] = $existingMitra->id;
                } else {
                    $namaClean = mb_substr($raw, 0, 200);
                    $newId = DB::table('mitra')->insertGetId([
                        'nama' => $namaClean,
                        'jenis' => 'toko',
                        'status' => 'aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $slugToMitraId[$slug] = $newId;
                }
            }

            DB::table('aset')->where('id', $row->id)->update([
                'mitra_id' => $slugToMitraId[$slug],
            ]);
        }

        // 3. Hapus kolom string lama toko_distributor
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('toko_distributor');
        });

        // 4. Tambah mitra_id ke staging aset_import_items
        Schema::table('aset_import_items', function (Blueprint $table) {
            $table->unsignedBigInteger('mitra_id')->nullable()->after('toko_distributor');
        });
    }

    public function down(): void
    {
        // 1. Restore kolom toko_distributor di aset
        Schema::table('aset', function (Blueprint $table) {
            $table->string('toko_distributor')->nullable()->after('tanggal_pembelian');
        });

        // 2. Backfill nama mitra kembali ke toko_distributor
        $asetList = DB::table('aset')
            ->join('mitra', 'aset.mitra_id', '=', 'mitra.id')
            ->select('aset.id', 'mitra.nama')
            ->get();

        foreach ($asetList as $item) {
            DB::table('aset')->where('id', $item->id)->update([
                'toko_distributor' => $item->nama,
            ]);
        }

        // 3. Drop kolom mitra_id dari staging
        Schema::table('aset_import_items', function (Blueprint $table) {
            $table->dropColumn('mitra_id');
        });

        // 4. Drop foreign key dan kolom mitra_id di aset
        Schema::table('aset', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['mitra_id']);
            }
            $table->dropColumn('mitra_id');
        });
    }
};
