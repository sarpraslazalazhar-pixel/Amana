<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_if(auth()->user()?->role !== 'super_admin', 403, 'Akses ditolak. Hanya Super Admin yang dapat mengakses Modul Log Audit.');

        $query = AuditLog::with(['user', 'aset']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset', 'LIKE', "%{$search}%")
                    ->orWhere('deskripsi', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        if ($request->filled('aset_id')) {
            $query->where('aset_id', $request->aset_id);
        }

        if ($request->filled('tgl_dari')) {
            $query->whereDate('created_at', '>=', $request->tgl_dari);
        }

        if ($request->filled('tgl_sampai')) {
            $query->whereDate('created_at', '<=', $request->tgl_sampai);
        }

        $summary = [
            'total_log' => AuditLog::count(),
            'log_hari_ini' => AuditLog::whereDate('created_at', today())->count(),
            'log_minggu_ini' => AuditLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total_update' => AuditLog::where('aksi', 'update_data')->count(),
        ];

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('audit_log.index', compact('logs', 'summary'));
    }
}
