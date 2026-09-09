<?php

namespace App\Services;

use App\Models\AgendaAset;
use App\Models\JurnalAset;
use App\Models\KeuanganAset;
use App\Models\RiwayatAset;
use Carbon\Carbon;

class KalenderAsetService
{
    /**
     * Nama hari dalam bahasa Indonesia ke nomor hari ISO (1 = Senin, 7 = Minggu)
     */
    private const HARI_MAP = [
        'senin' => 1,
        'selasa' => 2,
        'rabu' => 3,
        'kamis' => 4,
        'jumat' => 5,
        'sabtu' => 6,
        'minggu' => 7,
    ];

    /**
     * Dapatkan data lengkap kalender aset untuk bulan & tahun tertentu
     */
    public function getKalenderBulan(int $year, int $month, array $filters = []): array
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // Inisialisasi kerangka hari per tanggal dalam bulan aktif
        $daysData = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate->lessThanOrEqualTo($endOfMonth)) {
            $dateStr = $currentDate->toDateString();
            $daysData[$dateStr] = [
                'date' => $dateStr,
                'day_number' => $currentDate->day,
                'day_name' => $currentDate->translatedFormat('l'),
                'day_short' => $currentDate->translatedFormat('D'),
                'formatted_date' => $currentDate->translatedFormat('d M Y'),
                'is_today' => $currentDate->isToday(),
                'is_past' => $currentDate->isPast() && ! $currentDate->isToday(),
                'is_weekend' => $currentDate->isWeekend(),
                'agenda' => [],
                'jurnal' => [],
                'keuangan' => [],
                'riwayat' => [],
                'total_aktivitas' => 0,
            ];
            $currentDate->addDay();
        }

        $activeTypes = $filters['types'] ?? ['agenda', 'jurnal', 'keuangan', 'riwayat'];

        // 1. Ambil & Petakan Agenda Aset
        if (in_array('agenda', $activeTypes, true)) {
            $this->loadAgendaEvents($daysData, $startOfMonth, $endOfMonth, $filters);
        }

        // 2. Ambil & Petakan Jurnal Insiden / Perbaikan
        if (in_array('jurnal', $activeTypes, true)) {
            $this->loadJurnalEvents($daysData, $startOfMonth, $endOfMonth, $filters);
        }

        // 3. Ambil & Petakan Transaksi Keuangan
        if (in_array('keuangan', $activeTypes, true)) {
            $this->loadKeuanganEvents($daysData, $startOfMonth, $endOfMonth, $filters);
        }

        // 4. Ambil & Petakan Riwayat Perpindahan / Mutasi
        if (in_array('riwayat', $activeTypes, true)) {
            $this->loadRiwayatEvents($daysData, $startOfMonth, $endOfMonth, $filters);
        }

        // Hitung total aktivitas & ringkasan statistik
        $stats = [
            'total_aktivitas' => 0,
            'total_agenda' => 0,
            'agenda_pending' => 0,
            'agenda_selesai' => 0,
            'total_jurnal' => 0,
            'total_keuangan' => 0,
            'keuangan_pengeluaran' => 0,
            'total_riwayat' => 0,
            'hari_dengan_aktivitas' => 0,
        ];

        foreach ($daysData as $dateStr => &$day) {
            $count = count($day['agenda']) + count($day['jurnal']) + count($day['keuangan']) + count($day['riwayat']);
            $day['total_aktivitas'] = $count;

            if ($count > 0) {
                $stats['hari_dengan_aktivitas']++;
            }

            $stats['total_aktivitas'] += $count;
            $stats['total_agenda'] += count($day['agenda']);
            $stats['total_jurnal'] += count($day['jurnal']);
            $stats['total_keuangan'] += count($day['keuangan']);
            $stats['total_riwayat'] += count($day['riwayat']);

            foreach ($day['agenda'] as $ag) {
                if (($ag['status'] ?? '') === 'selesai') {
                    $stats['agenda_selesai']++;
                } else {
                    $stats['agenda_pending']++;
                }
            }

            foreach ($day['keuangan'] as $keu) {
                $stats['keuangan_pengeluaran'] += (float) ($keu['nominal'] ?? 0);
            }
        }
        unset($day);

        // Siapkan Matriks Grid Kalender (Senin - Minggu dengan padding sel kosong)
        $firstDayOfMonth = $startOfMonth->copy();
        $startDayOfWeek = $firstDayOfMonth->dayOfWeekIso; // 1 = Senin, 7 = Minggu
        $paddingBefore = $startDayOfWeek - 1;

        $lastDayOfMonth = $endOfMonth->copy();
        $endDayOfWeek = $lastDayOfMonth->dayOfWeekIso;
        $paddingAfter = 7 - $endDayOfWeek;

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->translatedFormat('F Y'),
            'prev_month' => $startOfMonth->copy()->subMonth()->month,
            'prev_year' => $startOfMonth->copy()->subMonth()->year,
            'next_month' => $startOfMonth->copy()->addMonth()->month,
            'next_year' => $startOfMonth->copy()->addMonth()->year,
            'days_data' => $daysData,
            'padding_before' => $paddingBefore,
            'padding_after' => $paddingAfter,
            'stats' => $stats,
        ];
    }

    /**
     * Memuat dan memetakan agenda berkala / spesifik ke kalender
     */
    private function loadAgendaEvents(array &$daysData, Carbon $startOfMonth, Carbon $endOfMonth, array $filters): void
    {
        $query = AgendaAset::with(['aset.kategori', 'aset.lokasi', 'aset.penanggungJawab', 'user']);

        $this->applyAsetFilters($query, $filters);

        $agendas = $query->get();
        $year = $startOfMonth->year;
        $month = $startOfMonth->month;
        $daysInMonth = $startOfMonth->daysInMonth;

        foreach ($agendas as $agenda) {
            $item = [
                'id' => $agenda->id,
                'type' => 'agenda',
                'title' => $agenda->nama_agenda,
                'tipe_agenda' => $agenda->tipe_agenda,
                'jadwal_teks' => $agenda->jadwal_teks,
                'biaya_estimasi' => (float) $agenda->biaya_estimasi,
                'status' => $agenda->status,
                'keterangan' => $agenda->keterangan,
                'user_name' => $agenda->user?->name ?? 'Sistem',
                'aset_id' => $agenda->aset_id,
                'kode_aset' => $agenda->aset?->kode_aset ?? '-',
                'nama_aset' => $agenda->aset?->nama_aset ?? 'Aset #'.$agenda->aset_id,
                'kategori_nama' => $agenda->aset?->kategori?->nama_kategori ?? '-',
                'lokasi_nama' => $agenda->aset?->lokasi?->nama_lokasi ?? '-',
                'pj_nama' => $agenda->aset?->penanggungJawab?->nama ?? '-',
            ];

            switch ($agenda->tipe_agenda) {
                case 'tanggal_tertentu':
                    if ($agenda->tanggal) {
                        $tgl = Carbon::parse($agenda->tanggal)->toDateString();
                        if (isset($daysData[$tgl])) {
                            $daysData[$tgl]['agenda'][] = $item;
                        }
                    }
                    break;

                case 'mingguan':
                    $targetDayOfWeek = self::HARI_MAP[strtolower($agenda->hari ?? 'senin')] ?? 1;
                    $cursor = $startOfMonth->copy();
                    while ($cursor->lessThanOrEqualTo($endOfMonth)) {
                        if ($cursor->dayOfWeekIso === $targetDayOfWeek) {
                            $daysData[$cursor->toDateString()]['agenda'][] = $item;
                        }
                        $cursor->addDay();
                    }
                    break;

                case 'bulanan':
                    $targetDay = (int) ($agenda->tanggal_hari ?: 1);
                    $clampedDay = min($targetDay, $daysInMonth);
                    $tgl = Carbon::create($year, $month, $clampedDay)->toDateString();
                    if (isset($daysData[$tgl])) {
                        $daysData[$tgl]['agenda'][] = $item;
                    }
                    break;

                case 'tahunan':
                    if ((int) $agenda->bulan === $month) {
                        $targetDay = (int) ($agenda->tanggal_hari ?: 1);
                        $clampedDay = min($targetDay, $daysInMonth);
                        $tgl = Carbon::create($year, $month, $clampedDay)->toDateString();
                        if (isset($daysData[$tgl])) {
                            $daysData[$tgl]['agenda'][] = $item;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Memuat dan memetakan catatan jurnal insiden / perbaikan
     */
    private function loadJurnalEvents(array &$daysData, Carbon $startOfMonth, Carbon $endOfMonth, array $filters): void
    {
        $query = JurnalAset::with(['aset.kategori', 'aset.lokasi', 'aset.penanggungJawab', 'user'])
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        $this->applyAsetFilters($query, $filters);

        $jurnals = $query->get();

        foreach ($jurnals as $jurnal) {
            $tgl = Carbon::parse($jurnal->tanggal)->toDateString();
            if (isset($daysData[$tgl])) {
                $daysData[$tgl]['jurnal'][] = [
                    'id' => $jurnal->id,
                    'type' => 'jurnal',
                    'title' => $jurnal->kejadian,
                    'tingkat_kerusakan' => $jurnal->tingkat_kerusakan,
                    'status_penanganan' => $jurnal->status_penanganan,
                    'lampiran' => $jurnal->lampiran_url,
                    'user_name' => $jurnal->user?->name ?? 'Sistem',
                    'aset_id' => $jurnal->aset_id,
                    'kode_aset' => $jurnal->aset?->kode_aset ?? '-',
                    'nama_aset' => $jurnal->aset?->nama_aset ?? 'Aset #'.$jurnal->aset_id,
                    'kategori_nama' => $jurnal->aset?->kategori?->nama_kategori ?? '-',
                    'lokasi_nama' => $jurnal->aset?->lokasi?->nama_lokasi ?? '-',
                    'pj_nama' => $jurnal->aset?->penanggungJawab?->nama ?? '-',
                ];
            }
        }
    }

    /**
     * Memuat dan memetakan catatan arus kas keuangan aset
     */
    private function loadKeuanganEvents(array &$daysData, Carbon $startOfMonth, Carbon $endOfMonth, array $filters): void
    {
        $query = KeuanganAset::with(['aset.kategori', 'aset.lokasi', 'aset.penanggungJawab', 'user'])
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        $this->applyAsetFilters($query, $filters);

        $transaksi = $query->get();

        foreach ($transaksi as $keu) {
            $tgl = Carbon::parse($keu->tanggal)->toDateString();
            if (isset($daysData[$tgl])) {
                $daysData[$tgl]['keuangan'][] = [
                    'id' => $keu->id,
                    'type' => 'keuangan',
                    'tipe' => $keu->tipe,
                    'title' => $keu->keterangan ?: ($keu->jenis_transaksi ?: 'Transaksi Keuangan'),
                    'jenis_transaksi' => $keu->jenis_transaksi,
                    'nominal' => (float) $keu->nominal,
                    'nominal_formatted' => 'Rp '.number_format($keu->nominal, 0, ',', '.'),
                    'user_name' => $keu->user?->name ?? 'Sistem',
                    'aset_id' => $keu->aset_id,
                    'kode_aset' => $keu->aset?->kode_aset ?? '-',
                    'nama_aset' => $keu->aset?->nama_aset ?? 'Aset #'.$keu->aset_id,
                    'kategori_nama' => $keu->aset?->kategori?->nama_kategori ?? '-',
                    'lokasi_nama' => $keu->aset?->lokasi?->nama_lokasi ?? '-',
                    'pj_nama' => $keu->aset?->penanggungJawab?->nama ?? '-',
                ];
            }
        }
    }

    /**
     * Memuat dan memetakan catatan riwayat / mutasi aset
     */
    private function loadRiwayatEvents(array &$daysData, Carbon $startOfMonth, Carbon $endOfMonth, array $filters): void
    {
        $query = RiwayatAset::with(['aset.kategori', 'penanggungJawab', 'lokasi', 'user'])
            ->whereBetween('sejak_tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        $this->applyAsetFilters($query, $filters);

        $riwayats = $query->get();

        foreach ($riwayats as $riwayat) {
            $tgl = Carbon::parse($riwayat->sejak_tanggal)->toDateString();
            if (isset($daysData[$tgl])) {
                $daysData[$tgl]['riwayat'][] = [
                    'id' => $riwayat->id,
                    'type' => 'riwayat',
                    'title' => $riwayat->keterangan ?: 'Perubahan Status / Mutasi Aset',
                    'jenis_aksi' => $riwayat->jenis_aksi,
                    'kondisi_persen' => $riwayat->kondisi_persen,
                    'kelengkapan_persen' => $riwayat->kelengkapan_persen,
                    'user_name' => $riwayat->user?->name ?? 'Sistem',
                    'aset_id' => $riwayat->aset_id,
                    'kode_aset' => $riwayat->aset?->kode_aset ?? '-',
                    'nama_aset' => $riwayat->aset?->nama_aset ?? 'Aset #'.$riwayat->aset_id,
                    'kategori_nama' => $riwayat->aset?->kategori?->nama_kategori ?? '-',
                    'lokasi_nama' => $riwayat->lokasi?->nama_lokasi ?? ($riwayat->aset?->lokasi?->nama_lokasi ?? '-'),
                    'pj_nama' => $riwayat->penanggungJawab?->nama ?? ($riwayat->aset?->penanggungJawab?->nama ?? '-'),
                ];
            }
        }
    }

    /**
     * Terapkan filter aset (kategori, lokasi, penanggung jawab, keyword pencarian)
     */
    private function applyAsetFilters($query, array $filters): void
    {
        $query->whereHas('aset', function ($q) use ($filters) {
            if (! empty($filters['kategori_id'])) {
                $q->where('kategori_id', $filters['kategori_id']);
            }
            if (! empty($filters['lokasi_id'])) {
                $q->where('lokasi_id', $filters['lokasi_id']);
            }
            if (! empty($filters['penanggung_jawab_id'])) {
                $q->where('penanggung_jawab_id', $filters['penanggung_jawab_id']);
            }
            if (! empty($filters['aset_id'])) {
                $q->where('id', $filters['aset_id']);
            }
            if (! empty($filters['q'])) {
                $keyword = '%'.$filters['q'].'%';
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('nama_aset', 'like', $keyword)
                        ->orWhere('kode_aset', 'like', $keyword)
                        ->orWhere('no_seri', 'like', $keyword);
                });
            }
        });
    }
}
