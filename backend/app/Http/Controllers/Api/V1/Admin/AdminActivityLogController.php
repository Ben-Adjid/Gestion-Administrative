<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminActivityLogController extends Controller
{
    /**
     * GET /api/v1/admin/activity-logs
     * Consultation des journaux d'audit (Réservé aux super_admin et admin)
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('admin:id,name,email,role');

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->query('admin_id'));
        }

        $logs = $query->latest()->paginate(25);

        return response()->json($logs);
    }
}
