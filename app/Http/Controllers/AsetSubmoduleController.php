<?php

namespace App\Http\Controllers;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\JurnalAset;
use App\Models\KeuanganAset;
use App\Models\Lokasi;
use App\Models\RiwayatAset;
use App\Services\AuditLogger;
use App\Services\ImageOptimizerService;
use App\Services\KodeAsetGenerator;
use Illuminate\Http\Request;

class AsetSubmoduleController extends Controller
{
    public function __construct(
        protected ImageOptimizerService $imageOptimizer
    ) {}

    /**
     * Preview Mutasi Kode Aset secara real-time untuk Modal Form
     */
    public function previewMutasi(Request $request, $asetId)
    {
        $aset = Aset::with(['kategori', 'barang', 'divisi'])->findOrFail($asetId);
        $pjId = $request->filled('penanggung_jawab_id') ? (int) $request->penanggung_jawab_id : null;
        $lokasiId = $request->filled('lokasi_id') ? (int) $request->lokasi_id : null;
        $divisiId = $request->filled('divisi_id') ? (int) $request->divisi_id : null;

        $res = KodeAsetGenerator::regenerateForMutation($aset, $pjId, $lokasiId, $divisiId);

        return response()->json($res);
    }

    /**
     * Mutasi / Tambah Riwayat Aset (Sesuai format Amana.md)
     * Format: sejak_tanggal, penanggung_jawab_id, lokasi_id, divisi_id, jumlah, kondisi, kelengkapan, keterangan
     */
    public function mutasi(Request $request, $asetId)
    {
        $request->validate([
            'sejak_tanggal' => 'required|date',
            'penanggung_jawab_id' => 'required|exists:penanggung_jawab,id',
            'lokasi_id' => 'required|exists:lokasi,id',
            'divisi_id' => 'nullable|exists:divisi,id',
            'jenis' => 'nullable|in:tetap,kelolaan',
            'jumlah' => 'nullable|integer|min:1',
            'kondisi_persen' => 'nullable|integer|min:0|max:100',
            'kelengkapan_persen' => 'nullable|integer|min:0|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $aset = Aset::with(['kategori', 'barang', 'divisi'])->findOrFail($asetId);
        $oldKode = $aset->kode_aset;
        $oldJenis = $aset->jenis;

        $targetDivisiId = $request->filled('divisi_id') ? (int) $request->divisi_id : null;
        $mutationResult = KodeAsetGenerator::regenerateForMutation(
            $aset,
            (int) $request->penanggung_jawab_id,
            (int) $request->lokasi_id,
            $targetDivisiId
        );

        // Update Penanggung Jawab, Lokasi, Divisi, dan Kode Aset
        $aset->penanggung_jawab_id = $request->penanggung_jawab_id;
        $aset->lokasi_id = $request->lokasi_id;
        if (! empty($mutationResult['new_divisi_id'])) {
            $aset->divisi_id = $mutationResult['new_divisi_id'];
        }

        // Tentukan Jenis Aset: Khusus Divisi 6 (Wakaf) fleksibel kelolaan / tetap (misal: pengadaan hak nazir)
        $activeDivisiId = $mutationResult['new_divisi_id'] ?? $targetDivisiId ?? $aset->divisi_id;
        $divisiBaru = \App\Models\Divisi::find($activeDivisiId);

        if ($divisiBaru && $divisiBaru->kode_divisi === '6') {
            $aset->jenis = in_array($request->jenis, ['tetap', 'kelolaan'], true)
                ? $request->jenis
                : ($aset->jenis ?: 'kelolaan');
        } elseif ($divisiBaru && $divisiBaru->kode_divisi === '5') {
            $aset->jenis = 'kelolaan';
        } elseif ($divisiBaru) {
            $aset->jenis = 'tetap';
        }

        if ($mutationResult['changed']) {
            $aset->kode_aset_lama = $oldKode;
            $aset->kode_aset = $mutationResult['new_code'];
        }
        $aset->save();

        $keteranganRiwayat = $request->keterangan;
        if ($oldJenis !== $aset->jenis) {
            $ketJenis = $aset->jenis === 'tetap' ? 'Aset Tetap (Hak Nazir)' : 'Aset Kelolaan';
            $keteranganRiwayat = trim(($keteranganRiwayat ? $keteranganRiwayat . '. ' : '') . "Jenis aset disesuaikan menjadi: {$ketJenis}.");
        }

        RiwayatAset::create([
            'aset_id' => $aset->id,
            'sejak_tanggal' => $request->sejak_tanggal,
            'penanggung_jawab_id' => $request->penanggung_jawab_id,
            'lokasi_id' => $request->lokasi_id,
            'divisi_id' => $mutationResult['new_divisi_id'] ?? $aset->divisi_id,
            'jumlah' => $request->jumlah ?? $aset->jumlah_unit ?? 1,
            'kondisi_persen' => $request->kondisi_persen ?? 100,
            'kelengkapan_persen' => $request->kelengkapan_persen ?? 100,
            'kode_aset_sebelumnya' => $oldKode,
            'kode_aset_baru' => $aset->kode_aset,
            'jenis_aksi' => 'mutasi',
            'keterangan' => $keteranganRiwayat,
            'user_id' => auth()->id() ?? 1,
        ]);

        $auditMsg = "Mutasi aset {$aset->nama_aset}: ";
        if ($mutationResult['changed']) {
            $auditMsg .= "Kode aset diperbarui dari {$oldKode} ke {$aset->kode_aset}. ";
        }
        $auditMsg .= 'Lokasi, Penanggung Jawab, dan Divisi disinkronkan.';
        AuditLogger::log('mutasi', $auditMsg, $aset);

        $successMsg = $mutationResult['changed']
            ? "Riwayat mutasi berhasil dicatat. Kode Aset otomatis diperbarui menjadi {$aset->kode_aset} (sebelumnya: {$oldKode})."
            : 'Riwayat mutasi berhasil dicatat dan data pemegang/lokasi telah diperbarui.';

        return redirect()->route('aset.show', $aset->id)->with('success', $successMsg);
    }

    /**
     * Tambah Catatan Riwayat Sederhana
     */
    public function storeRiwayat(Request $request, $asetId)
    {
        $request->validate([
            'sejak_tanggal' => 'required|date',
            'keterangan' => 'required|string|max:500',
        ]);

        $aset = Aset::findOrFail($asetId);

        RiwayatAset::create([
            'aset_id' => $aset->id,
            'sejak_tanggal' => $request->sejak_tanggal,
            'penanggung_jawab_id' => $aset->penanggung_jawab_id,
            'lokasi_id' => $aset->lokasi_id,
            'jumlah' => $aset->jumlah_unit ?? 1,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'catatan',
            'keterangan' => $request->keterangan,
            'user_id' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('mutasi', "Penambahan catatan riwayat fisik pada {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Catatan riwayat berhasil ditambahkan.');
    }

    /**
     * Tambah Agenda / Kalender Kegiatan (Sesuai format Amana.md)
     * Format: Tipe (Mingguan: hari apa / Bulanan: tgl berapa / Tahunan: bln & tgl apa / Tertentu: tgl pasti), Nama Agenda, Keterangan, Biaya
     */
    public function storeAgenda(Request $request, $asetId)
    {
        if ($request->filled('biaya_estimasi')) {
            $request->merge(['biaya_estimasi' => str_replace('.', '', (string) $request->biaya_estimasi)]);
        }

        $request->validate([
            'tipe_agenda' => 'required|in:mingguan,bulanan,tahunan,tanggal_tertentu',
            'nama_agenda' => 'required|string|max:255',
            'hari' => 'nullable|string|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_hari' => 'nullable|integer|min:1|max:28',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tanggal' => 'nullable|date',
            'biaya_estimasi' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $aset = Aset::findOrFail($asetId);

        AgendaAset::create([
            'aset_id' => $aset->id,
            'tipe_agenda' => $request->tipe_agenda,
            'nama_agenda' => $request->nama_agenda,
            'hari' => $request->tipe_agenda === 'mingguan' ? $request->hari : null,
            'tanggal_hari' => in_array($request->tipe_agenda, ['bulanan', 'tahunan']) ? $request->tanggal_hari : null,
            'bulan' => $request->tipe_agenda === 'tahunan' ? $request->bulan : null,
            'tanggal' => $request->tipe_agenda === 'tanggal_tertentu' ? $request->tanggal : null,
            'biaya_estimasi' => $request->biaya_estimasi ?? 0,
            'keterangan' => $request->keterangan,
            'status' => 'pending',
            'user_id' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('agenda', "Penjadwalan agenda '{$request->nama_agenda}' pada {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Agenda kegiatan berhasil dijadwalkan.');
    }

    /**
     * Selesaikan Agenda Kegiatan Aset:
     * Menyimpan hasil pengerjaan, mengunggah dokumen/lampiran,
     * otomatis mencatat ke Jurnal Kejadian Aset,
     * serta mencatat ke Keuangan Aset jika terdapat realisasi biaya.
     */
    public function selesaikanAgenda(Request $request, $agendaId)
    {
        if ($request->filled('biaya_riil')) {
            $request->merge(['biaya_riil' => str_replace('.', '', (string) $request->biaya_riil)]);
        }

        $request->validate([
            'tanggal_selesai' => 'required|date',
            'catatan_penyelesaian' => 'nullable|string',
            'biaya_riil' => 'nullable|numeric|min:0',
            'lampiran' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx',
        ]);

        $agenda = AgendaAset::with('aset')->findOrFail($agendaId);
        $aset = $agenda->aset;

        // Upload lampiran dokumen jika ada
        $lampiranPath = null;
        if ($request->hasFile('lampiran') && $request->file('lampiran')->isValid()) {
            $file = $request->file('lampiran');
            if ($file->getRealPath()) {
                $lampiranPath = $this->imageOptimizer->storeAttachmentSmart($file, 'jurnal_lampiran', 'public');
            }
        }

        $tanggalSelesai = $request->tanggal_selesai;
        $catatan = $request->catatan_penyelesaian;
        $biayaRiil = (float) ($request->biaya_riil ?? 0);
        $userId = auth()->id() ?? 1;

        // 1. Catat ke Jurnal Kejadian Aset (hanya jika ada lampiran/foto yang diunggah)
        if ($lampiranPath) {
            $kejadianJurnal = "Penyelesaian Agenda: {$agenda->nama_agenda}";
            if ($catatan) {
                $kejadianJurnal .= "\nCatatan: {$catatan}";
            }

            JurnalAset::create([
                'aset_id' => $aset->id,
                'agenda_id' => $agenda->id,
                'tanggal' => $tanggalSelesai,
                'kejadian' => $kejadianJurnal,
                'lampiran' => $lampiranPath,
                'tingkat_kerusakan' => null,
                'status_penanganan' => 'selesai',
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);
        }

        // 2. Jika ada biaya riil > 0, catat ke Keuangan Aset (Pengeluaran)
        if ($biayaRiil > 0) {
            KeuanganAset::create([
                'aset_id' => $aset->id,
                'agenda_id' => $agenda->id,
                'tipe' => 'pengeluaran',
                'tanggal' => $tanggalSelesai,
                'nominal' => $biayaRiil,
                'jenis_transaksi' => "Pemeliharaan / Agenda: {$agenda->nama_agenda}",
                'keterangan' => "Realisasi biaya agenda {$agenda->nama_agenda}.".($catatan ? " Catatan: {$catatan}" : ''),
                'user_id' => $userId,
                'updated_by' => $userId,
            ]);
        }

        // 3. Perbarui status agenda menjadi selesai
        $agenda->status = 'selesai';
        $agenda->tanggal_selesai = $tanggalSelesai;
        $agenda->catatan_penyelesaian = $catatan;
        if ($lampiranPath) {
            $agenda->lampiran_penyelesaian = $lampiranPath;
        }
        $agenda->biaya_riil = $biayaRiil;
        $agenda->updated_by = $userId;
        $agenda->save();

        $auditDeskripsi = "Penyelesaian agenda '{$agenda->nama_agenda}' pada {$aset->kode_aset}."
            .($lampiranPath ? ' Tercatat ke Jurnal (ada lampiran foto/dokumen).' : '')
            .($biayaRiil > 0 ? ' Tercatat ke Keuangan (Rp '.number_format($biayaRiil, 0, ',', '.').').' : '');
        AuditLogger::log('agenda', $auditDeskripsi, $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Agenda berhasil diselesaikan! Bukti/catatan telah otomatis dicatat ke Jurnal'.($biayaRiil > 0 ? ' dan Pengeluaran Keuangan.' : '.'));
    }

    /**
     * Toggle Status Agenda (Pending / Selesai)
     */
    public function toggleAgendaStatus($agendaId)
    {
        $agenda = AgendaAset::findOrFail($agendaId);
        $userId = auth()->id() ?? 1;

        if ($agenda->status === 'selesai') {
            $agenda->status = 'pending';
            $agenda->updated_by = $userId;
            $agenda->save();

            AuditLogger::log('agenda', "Status agenda '{$agenda->nama_agenda}' dikembalikan ke pending", $agenda->aset);

            return back()->with('success', 'Status agenda dikembalikan menjadi pending. Data jurnal dan riwayat keuangan tetap disimpan.');
        } else {
            $agenda->status = 'selesai';
            $agenda->tanggal_selesai = now()->toDateString();
            $agenda->updated_by = $userId;
            $agenda->save();

            return back()->with('success', 'Status agenda berhasil ditandai selesai.');
        }
    }

    /**
     * Catat Keuangan / Pengeluaran Biaya Aset
     * Format: Tanggal lengkap, Nominal (Rp), Jenis Transaksi, Keterangan
     */
    public function storeKeuangan(Request $request, $asetId)
    {
        if ($request->filled('nominal')) {
            $request->merge(['nominal' => str_replace('.', '', (string) $request->nominal)]);
        }

        $request->validate([
            'tipe' => 'nullable|in:pengeluaran',
            'tanggal' => 'required|date',
            'nominal' => 'required|numeric|min:0',
            'jenis_transaksi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $aset = Aset::findOrFail($asetId);

        KeuanganAset::create([
            'aset_id' => $aset->id,
            'tipe' => 'pengeluaran',
            'tanggal' => $request->tanggal,
            'nominal' => $request->nominal,
            'jenis_transaksi' => $request->jenis_transaksi ?: 'Biaya Perawatan',
            'keterangan' => $request->keterangan,
            'user_id' => auth()->id() ?? 1,
        ]);

        $nominalFormatted = 'Rp '.number_format((float) $request->nominal, 0, ',', '.');
        AuditLogger::log('keuangan', "Pengeluaran biaya sebesar {$nominalFormatted} dicatat pada {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Pengeluaran biaya aset berhasil dicatat.');
    }

    /**
     * Lapor Jurnal Kejadian Aset (Sesuai Amana.md)
     * Format: Tanggal, Kejadian, Lampiran (Upload File)
     */
    public function storeJurnal(Request $request, $asetId)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'kejadian' => 'required|string',
            'lampiran' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx',
        ]);

        $aset = Aset::findOrFail($asetId);

        $lampiranPath = null;
        if ($request->hasFile('lampiran') && $request->file('lampiran')->isValid()) {
            $file = $request->file('lampiran');
            if ($file->getRealPath()) {
                $lampiranPath = $this->imageOptimizer->storeAttachmentSmart($file, 'jurnal_lampiran', 'public');
            }
        }

        JurnalAset::create([
            'aset_id' => $aset->id,
            'tanggal' => $request->tanggal,
            'kejadian' => $request->kejadian,
            'lampiran' => $lampiranPath,
            'user_id' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('jurnal', "Laporan insiden jurnal kejadian pada {$aset->kode_aset}: {$request->kejadian}", $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Catatan jurnal kejadian berhasil disimpan.');
    }

    /**
     * Update Status Penanganan Jurnal
     */
    public function updateJurnalStatus(Request $request, $jurnalId)
    {
        $request->validate([
            'status_penanganan' => 'required|in:belum_ditangani,dalam_perbaikan,selesai',
        ]);

        $jurnal = JurnalAset::findOrFail($jurnalId);
        $jurnal->status_penanganan = $request->status_penanganan;
        $jurnal->updated_by = auth()->id() ?? 1;
        $jurnal->save();

        return back()->with('success', 'Status penanganan insiden berhasil diperbarui.');
    }

    /**
     * Ubah Status Aset (Aktif / Non-Aktif)
     */
    public function ubahStatus(Request $request, $asetId)
    {
        $request->validate([
            'status' => 'required|in:aktif,non_aktif',
            'alasan' => 'nullable|string|max:500',
        ]);

        $aset = Aset::findOrFail($asetId);
        $statusLama = $aset->status;
        $aset->status = $request->status;
        if ($request->status === 'non_aktif') {
            $aset->nonaktif_sebab = $request->input('sebab', $request->input('alasan', 'Purna Manfaat / Rusak'));
            $aset->nonaktif_keterangan = $request->input('keterangan', $request->input('alasan'));
        } else {
            $aset->nonaktif_sebab = null;
            $aset->nonaktif_keterangan = null;
        }
        $aset->save();

        $alasan = $request->filled('alasan') ? ' (Alasan: '.$request->alasan.')' : '';
        RiwayatAset::create([
            'aset_id' => $aset->id,
            'sejak_tanggal' => now()->toDateString(),
            'penanggung_jawab_id' => $aset->penanggung_jawab_id,
            'lokasi_id' => $aset->lokasi_id,
            'jumlah' => $aset->jumlah_unit ?? 1,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'ubah_status',
            'keterangan' => "Status aset diubah dari '{$statusLama}' menjadi '{$request->status}'{$alasan}",
            'user_id' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('ubah_status', "Status aset {$aset->kode_aset} diubah dari '{$statusLama}' menjadi '{$request->status}'", $aset);

        return redirect()->route('aset.show', $aset->id)->with('success', 'Status aset berhasil diperbarui.');
    }

    /**
     * Update Data Riwayat Aset
     */
    public function updateRiwayat(Request $request, $riwayatId)
    {
        $riwayat = RiwayatAset::findOrFail($riwayatId);
        $aset = $riwayat->aset;

        $request->validate([
            'sejak_tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'kondisi_persen' => 'required|integer|min:0|max:100',
            'kelengkapan_persen' => 'required|integer|min:0|max:100',
        ]);

        $riwayat->update([
            'sejak_tanggal' => $request->sejak_tanggal,
            'keterangan' => $request->keterangan,
            'kondisi_persen' => $request->kondisi_persen,
            'kelengkapan_persen' => $request->kelengkapan_persen,
            'updated_by' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('riwayat', "Pembaruan catatan riwayat pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'riwayat'])
            ->with('success', 'Catatan riwayat aset berhasil diperbarui.');
    }

    /**
     * Hapus Data Riwayat Aset (Proteksi untuk jenis pembuatan)
     */
    public function destroyRiwayat($riwayatId)
    {
        $riwayat = RiwayatAset::findOrFail($riwayatId);
        $aset = $riwayat->aset;

        if ($riwayat->jenis_aksi === 'pembuatan') {
            return back()->with('error', 'Riwayat awal pembuatan aset dilindungi dan tidak dapat dihapus.');
        }

        $riwayat->delete();

        AuditLogger::log('riwayat', "Penghapusan riwayat aktivitas pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'riwayat'])
            ->with('success', 'Catatan riwayat berhasil dihapus.');
    }

    /**
     * Update Agenda Kegiatan Aset
     */
    public function updateAgenda(Request $request, $agendaId)
    {
        $agenda = AgendaAset::findOrFail($agendaId);
        $aset = $agenda->aset;

        if ($request->filled('biaya_estimasi')) {
            $request->merge(['biaya_estimasi' => str_replace('.', '', (string) $request->biaya_estimasi)]);
        }

        $request->validate([
            'tipe_agenda' => 'required|in:mingguan,bulanan,tahunan,tanggal_tertentu',
            'nama_agenda' => 'required|string|max:255',
            'hari' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_hari' => 'nullable|integer|min:1|max:31',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tanggal' => 'nullable|date',
            'biaya_estimasi' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $agenda->update([
            'tipe_agenda' => $request->tipe_agenda,
            'nama_agenda' => $request->nama_agenda,
            'hari' => $request->tipe_agenda === 'mingguan' ? $request->hari : null,
            'tanggal_hari' => in_array($request->tipe_agenda, ['bulanan', 'tahunan']) ? $request->tanggal_hari : null,
            'bulan' => $request->tipe_agenda === 'tahunan' ? $request->bulan : null,
            'tanggal' => $request->tipe_agenda === 'tanggal_tertentu' ? $request->tanggal : null,
            'biaya_estimasi' => $request->biaya_estimasi ?: 0,
            'keterangan' => $request->keterangan,
            'updated_by' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('agenda', "Pembaruan agenda '{$agenda->nama_agenda}' pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'agenda'])
            ->with('success', 'Agenda kegiatan berhasil diperbarui.');
    }

    /**
     * Hapus Agenda Kegiatan Aset
     */
    public function destroyAgenda($agendaId)
    {
        $agenda = AgendaAset::findOrFail($agendaId);
        $aset = $agenda->aset;
        $namaAgenda = $agenda->nama_agenda;

        $agenda->delete();

        AuditLogger::log('agenda', "Penghapusan agenda '{$namaAgenda}' pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'agenda'])
            ->with('success', 'Agenda berhasil dihapus.');
    }

    /**
     * Update Data Keuangan / Biaya Aset
     */
    public function updateKeuangan(Request $request, $keuanganId)
    {
        $keuangan = KeuanganAset::findOrFail($keuanganId);
        $aset = $keuangan->aset;

        if ($request->filled('nominal')) {
            $request->merge(['nominal' => str_replace('.', '', (string) $request->nominal)]);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'nominal' => 'required|numeric|min:0',
            'jenis_transaksi' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $keuangan->update([
            'tanggal' => $request->tanggal,
            'nominal' => $request->nominal,
            'jenis_transaksi' => $request->jenis_transaksi,
            'keterangan' => $request->keterangan,
            'updated_by' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('keuangan', "Pembaruan transaksi keuangan pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'keuangan'])
            ->with('success', 'Catatan keuangan berhasil diperbarui.');
    }

    /**
     * Hapus Data Keuangan Aset
     */
    public function destroyKeuangan($keuanganId)
    {
        $keuangan = KeuanganAset::findOrFail($keuanganId);
        $aset = $keuangan->aset;

        if ($keuangan->is_dari_agenda) {
            return back()->with('error', 'Catatan pengeluaran keuangan yang dibuat otomatis dari penyelesaian agenda tidak dapat dihapus.');
        }

        $keuangan->delete();

        AuditLogger::log('keuangan', "Penghapusan transaksi keuangan pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'keuangan'])
            ->with('success', 'Catatan keuangan berhasil dihapus.');
    }

    /**
     * Update Catatan Jurnal Aset (Hanya untuk jurnal manual, bukan otomatis dari agenda)
     */
    public function updateJurnal(Request $request, $jurnalId)
    {
        $jurnal = JurnalAset::findOrFail($jurnalId);
        $aset = $jurnal->aset;

        if ($jurnal->is_dari_agenda) {
            return back()->with('error', 'Entri jurnal yang dibuat otomatis dari penyelesaian agenda tidak dapat diubah langsung.');
        }

        $request->validate([
            'tanggal' => 'required|date',
            'kejadian' => 'required|string',
            'lampiran' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx',
        ]);

        $lampiranPath = $jurnal->lampiran;
        if ($request->hasFile('lampiran') && $request->file('lampiran')->isValid()) {
            $file = $request->file('lampiran');
            if ($file->getRealPath()) {
                $newLampiranPath = $this->imageOptimizer->storeAttachmentSmart($file, 'jurnal_lampiran', 'public');
                if ($jurnal->lampiran && $this->imageOptimizer->isImage($jurnal->lampiran)) {
                    $this->imageOptimizer->deleteOldImage($jurnal->lampiran);
                }
                $lampiranPath = $newLampiranPath;
            }
        }

        $jurnal->update([
            'tanggal' => $request->tanggal,
            'kejadian' => $request->kejadian,
            'lampiran' => $lampiranPath,
            'updated_by' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('jurnal', "Pembaruan jurnal kejadian pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'jurnal'])
            ->with('success', 'Catatan jurnal kejadian berhasil diperbarui.');
    }

    /**
     * Hapus Catatan Jurnal Aset (Hanya untuk jurnal manual, bukan otomatis dari agenda)
     */
    public function destroyJurnal($jurnalId)
    {
        $jurnal = JurnalAset::findOrFail($jurnalId);
        $aset = $jurnal->aset;

        if ($jurnal->is_dari_agenda) {
            return back()->with('error', 'Entri jurnal yang dibuat otomatis dari penyelesaian agenda tidak dapat dihapus.');
        }

        $jurnal->delete();

        AuditLogger::log('jurnal', "Penghapusan catatan jurnal pada aset {$aset->kode_aset}", $aset);

        return redirect()->route('aset.show', ['aset' => $aset->id, 'tab' => 'jurnal'])
            ->with('success', 'Catatan jurnal kejadian berhasil dihapus.');
    }
}
