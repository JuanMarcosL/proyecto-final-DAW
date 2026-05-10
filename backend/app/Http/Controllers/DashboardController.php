<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Appointment;
use App\Models\Resource;
use App\Models\Absence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'all');

        $from = match($period) {
            'today' => Carbon::today(),
            'week'  => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $woQuery  = WorkOrder::query();
        $appQuery = Appointment::query();
        $absQuery = Absence::query();

        if ($from) {
            $woQuery->where('created_at', '>=', $from);
            $appQuery->where('created_at', '>=', $from);
            $absQuery->where('created_at', '>=', $from);
        }

        return response()->json([
            'work_orders' => [
                'total'       => (clone $woQuery)->count(),
                'open'        => (clone $woQuery)->where('status', 'open')->count(),
                'in_progress' => (clone $woQuery)->where('status', 'in_progress')->count(),
                'closed'      => (clone $woQuery)->where('status', 'closed')->count(),
                'cancelled'   => (clone $woQuery)->where('status', 'cancelled')->count(),
            ],
            'appointments' => [
                'total'     => (clone $appQuery)->count(),
                'draft'     => (clone $appQuery)->where('status', 'draft')->count(),
                'scheduled' => (clone $appQuery)->where('status', 'scheduled')->count(),
                'completed' => (clone $appQuery)->where('status', 'completed')->count(),
            ],
            'resources' => [
                'total'  => Resource::count(),
                'active' => Resource::where('active', true)->count(),
            ],
            'absences' => [
                'pending'  => (clone $absQuery)->where('status', 'pending')->count(),
                'approved' => (clone $absQuery)->where('status', 'approved')->count(),
            ],
            'recent_work_orders' => WorkOrder::with('creator')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ]);
    }
}
