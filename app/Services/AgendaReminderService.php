<?php

namespace App\Services;

use App\Models\AgendaAset;
use Carbon\Carbon;

class AgendaReminderService
{
    private const HARI_MAP = [
        'senin' => 1,
        'selasa' => 2,
        'rabu' => 3,
        'kamis' => 4,
        'jumat' => 5,
        'sabtu' => 6,
        'minggu' => 7,
    ];

    private ?array $cachedData = null;

    /**
     * Dapatkan semua notifikasi pengingat agenda yang memerlukan perhatian
     * (Terlambat, Jatuh Tempo Hari Ini, dan Akan Datang dalam 7 hari ke depan).
     */
    public function getReminderData(bool $forceRefresh = false): array
    {
        if ($this->cachedData !== null && ! $forceRefresh) {
            return $this->cachedData;
        }

        $now = Carbon::now()->startOfDay();
        $limitUpcoming = $now->copy()->addDays(7);

        // Ambil seluruh agenda berstatus pending yang asetnya masih aktif
        $agendas = AgendaAset::with(['aset.kategori', 'aset.lokasi', 'aset.penanggungJawab'])
            ->where('status', 'pending')
            ->whereHas('aset', function ($q) {
                $q->where('status', 'aktif');
            })
            ->get();

        $items = collect();

        foreach ($agendas as $agenda) {
            $dueDate = $this->calculateDueDate($agenda, $now);
            if (! $dueDate) {
                continue;
            }

            $category = null;
            $diffDays = 0;
            $humanDiff = '';

            if ($dueDate->lt($now)) {
                $category = 'overdue';
                $diffDays = (int) $dueDate->diffInDays($now);
                $humanDiff = $diffDays === 1 ? 'Terlambat 1 hari yang lalu' : "Terlambat {$diffDays} hari yang lalu";
            } elseif ($dueDate->eq($now)) {
                $category = 'today';
                $diffDays = 0;
                $humanDiff = 'Jatuh tempo hari ini';
            } elseif ($dueDate->lte($limitUpcoming)) {
                $category = 'upcoming';
                $diffDays = (int) $now->diffInDays($dueDate);
                $humanDiff = $diffDays === 1 ? 'Besok (1 hari lagi)' : "{$diffDays} hari lagi";
            }

            // Hanya sertakan jika masuk kategori overdue, today, atau upcoming (<= 7 hari)
            if ($category !== null) {
                $items->push([
                    'id' => $agenda->id,
                    'nama_agenda' => $agenda->nama_agenda,
                    'tipe_agenda' => $agenda->tipe_agenda,
                    'tipe_label' => ucfirst(str_replace('_', ' ', $agenda->tipe_agenda)),
                    'jadwal_teks' => $agenda->jadwal_teks,
                    'keterangan' => $agenda->keterangan,
                    'biaya_estimasi' => (float) $agenda->biaya_estimasi,
                    'due_date' => $dueDate,
                    'due_date_formatted' => $dueDate->translatedFormat('d M Y'),
                    'due_date_iso' => $dueDate->toDateString(),
                    'category' => $category,
                    'diff_days' => $diffDays,
                    'human_diff' => $humanDiff,
                    'aset_id' => $agenda->aset_id,
                    'kode_aset' => $agenda->aset?->kode_aset ?? '-',
                    'nama_aset' => $agenda->aset?->nama_aset ?? 'Aset #'.$agenda->aset_id,
                    'kategori_nama' => $agenda->aset?->kategori?->nama_kategori ?? '-',
                    'lokasi_nama' => $agenda->aset?->lokasi?->nama_lokasi ?? '-',
                    'pj_nama' => $agenda->aset?->penanggungJawab?->nama ?? '-',
                ]);
            }
        }

        // Urutkan: overdue (terlama lebih dulu) -> today -> upcoming (terdekat lebih dulu)
        $sortedItems = $items->sort(function ($a, $b) {
            $priority = ['overdue' => 1, 'today' => 2, 'upcoming' => 3];
            $pDiff = $priority[$a['category']] <=> $priority[$b['category']];
            if ($pDiff !== 0) {
                return $pDiff;
            }

            // Jika sesama overdue, yang paling lama terlambat (due_date terkecil) di atas
            if ($a['category'] === 'overdue') {
                return $a['due_date']->timestamp <=> $b['due_date']->timestamp;
            }

            // Jika today atau upcoming, due_date terdekat di atas
            return $a['due_date']->timestamp <=> $b['due_date']->timestamp;
        })->values();

        $overdueCount = $sortedItems->where('category', 'overdue')->count();
        $todayCount = $sortedItems->where('category', 'today')->count();
        $upcomingCount = $sortedItems->where('category', 'upcoming')->count();
        $totalCount = $sortedItems->count();
        $criticalCount = $overdueCount + $todayCount;

        $this->cachedData = [
            'items' => $sortedItems,
            'overdue_items' => $sortedItems->where('category', 'overdue')->values(),
            'today_items' => $sortedItems->where('category', 'today')->values(),
            'upcoming_items' => $sortedItems->where('category', 'upcoming')->values(),
            'total_count' => $totalCount,
            'critical_count' => $criticalCount,
            'overdue_count' => $overdueCount,
            'today_count' => $todayCount,
            'upcoming_count' => $upcomingCount,
            'has_reminders' => $totalCount > 0,
            'has_critical' => $criticalCount > 0,
        ];

        return $this->cachedData;
    }

    /**
     * Hitung tanggal jatuh tempo target untuk sebuah agenda.
     */
    public function calculateDueDate(AgendaAset $agenda, ?Carbon $now = null): ?Carbon
    {
        $now = $now ? $now->copy()->startOfDay() : Carbon::now()->startOfDay();

        switch ($agenda->tipe_agenda) {
            case 'tanggal_tertentu':
                if (! $agenda->tanggal) {
                    return null;
                }

                return Carbon::parse($agenda->tanggal)->startOfDay();

            case 'mingguan':
                $targetIso = self::HARI_MAP[strtolower($agenda->hari ?? 'senin')] ?? 1;
                $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);

                return $startOfWeek->addDays($targetIso - 1)->startOfDay();

            case 'bulanan':
                $day = min((int) ($agenda->tanggal_hari ?: 1), $now->daysInMonth);

                return Carbon::create($now->year, $now->month, $day)->startOfDay();

            case 'tahunan':
                $targetBulan = (int) ($agenda->bulan ?: 1);
                if ($targetBulan < 1 || $targetBulan > 12) {
                    $targetBulan = 1;
                }
                $daysInTargetMonth = Carbon::create($now->year, $targetBulan, 1)->daysInMonth;
                $day = min((int) ($agenda->tanggal_hari ?: 1), $daysInTargetMonth);

                return Carbon::create($now->year, $targetBulan, $day)->startOfDay();

            default:
                return null;
        }
    }
}
