<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Appointment;
use App\Models\Resource;
use App\Models\Absence;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $resources = Resource::with(['user', 'appointments', 'absences'])->get();

        return response()->json([
            'wo_by_status' => [
                'open'        => WorkOrder::where('status', 'open')->count(),
                'in_progress' => WorkOrder::where('status', 'in_progress')->count(),
                'closed'      => WorkOrder::where('status', 'closed')->count(),
                'cancelled'   => WorkOrder::where('status', 'cancelled')->count(),
            ],
            'wo_by_priority' => [
                'low'      => WorkOrder::where('priority', 'low')->count(),
                'medium'   => WorkOrder::where('priority', 'medium')->count(),
                'high'     => WorkOrder::where('priority', 'high')->count(),
                'critical' => WorkOrder::where('priority', 'critical')->count(),
            ],
            'appointments_by_resource' => $resources->map(fn($r) => [
                'name'  => $r->user->name,
                'total' => $r->appointments->count(),
            ]),
            'absences_by_resource' => $resources->map(fn($r) => [
                'name' => $r->user->name,
                'days' => $r->absences->where('status', 'approved')->sum(fn($a) =>
                    \Carbon\Carbon::parse($a->start_date)->diffInDays(\Carbon\Carbon::parse($a->end_date)) + 1
                ),
            ]),
            'work_orders' => WorkOrder::with(['creator', 'updater'])->orderBy('created_at', 'desc')->get(),
        ]);
    }
}
