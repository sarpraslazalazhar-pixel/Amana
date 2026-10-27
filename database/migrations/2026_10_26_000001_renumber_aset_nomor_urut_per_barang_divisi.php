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
        $groups = DB::table('aset')
            ->select('barang_id', 'divisi_id')
            ->groupBy('barang_id', 'divisi_id')
            ->get();

        $updates = [];

        foreach ($groups as $group) {
            $asets = DB::table('aset')
                ->where('barang_id', $group->barang_id)
                ->where('divisi_id', $group->divisi_id)
                ->orderBy('tanggal_pembelian')
                ->orderBy('id')
                ->get(['id', 'kode_aset', 'kode_aset_lama', 'nomor_urut']);

            $seq = 1;
            foreach ($asets as $aset) {
                $newPad = str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
                $base = substr($aset->kode_aset, 0, -2);
                $newKode = $base.$newPad;

                $update = [
                    'id' => $aset->id,
                    'nomor_urut' => $seq,
                    'kode_aset' => $newKode,
                    'kode_aset_lama' => null,
                ];

                if ($aset->kode_aset !== $newKode && empty($aset->kode_aset_lama)) {
                    $update['kode_aset_lama'] = $aset->kode_aset;
                }

                $updates[] = $update;
                $seq++;
            }
        }

        DB::transaction(function () use ($updates) {
            // Tahap 1: Berikan kode sementara agar tidak melanggar unique constraint MySQL
            foreach ($updates as $u) {
                DB::table('aset')->where('id', $u['id'])->update([
                    'kode_aset' => 'TMP_'.$u['id'],
                ]);
            }

            // Tahap 2: Terapkan kode_aset final, nomor_urut baru, dan preserve kode_aset_lama
            foreach ($updates as $u) {
                $data = [
                    'nomor_urut' => $u['nomor_urut'],
                    'kode_aset' => $u['kode_aset'],
                ];
                if ($u['kode_aset_lama'] !== null) {
                    $data['kode_aset_lama'] = $u['kode_aset_lama'];
                }
                DB::table('aset')->where('id', $u['id'])->update($data);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible: old nomor_urut per-tahun data overwritten.
        // QR fallback via kode_aset_lama preserves scan access.
    }
};
