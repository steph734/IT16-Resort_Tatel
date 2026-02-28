<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Audit_Log;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $action   = $request->input('action');
        $userId   = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $query = Audit_Log::with('user')->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit-logs', [
            'logs'     => $logs,
            'search'   => $search,
            'action'   => $action,
            'userId'   => $userId,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }
}