<?php

namespace App\Providers;

use App\Services\AgendaReminderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AgendaReminderService::class, function () {
            return new AgendaReminderService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('keuangan_aset') && ! \Illuminate\Support\Facades\Schema::hasColumn('keuangan_aset', 'lampiran')) {
                \Illuminate\Support\Facades\Schema::table('keuangan_aset', function ($table) {
                    $table->string('lampiran', 500)->nullable()->after('keterangan');
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('divisi')) {
                \Illuminate\Support\Facades\DB::table('divisi')
                    ->where('kode_divisi', '7')
                    ->where('nama_divisi', 'LIKE', '%Satuan Audit Internal%')
                    ->update([
                        'nama_divisi' => 'Kantor Perwakilan (KPw)',
                        'keterangan' => 'Aset',
                    ]);
            }
        } catch (\Throwable $e) {
            // Abaikan jika koneksi db belum siap
        }

        View::composer(['layouts.header', 'dashboard.index'], function ($view) {
            if (Auth::check()) {
                $service = app(AgendaReminderService::class);
                $view->with('agendaReminder', $service->getReminderData());
            } else {
                $view->with('agendaReminder', [
                    'items' => collect(),
                    'overdue_items' => collect(),
                    'today_items' => collect(),
                    'upcoming_items' => collect(),
                    'total_count' => 0,
                    'critical_count' => 0,
                    'overdue_count' => 0,
                    'today_count' => 0,
                    'upcoming_count' => 0,
                    'has_reminders' => false,
                    'has_critical' => false,
                ]);
            }
        });
    }
}
