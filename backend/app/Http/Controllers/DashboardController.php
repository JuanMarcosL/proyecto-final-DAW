<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Appointment;
use App\Models\Resource;
use App\Models\Absence;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'work_orders' => [
                'total'       => WorkOrder::count(),
                'open'        => WorkOrder::where('status', 'open')->count(),
                'in_progress' => WorkOrder::where('status', 'in_progress')->count(),
                'closed'      => WorkOrder::where('status', 'closed')->count(),
                'cancelled'   => WorkOrder::where('status', 'cancelled')->count(),
            ],
            'appointments' => [
                'total'       => Appointment::count(),
                'draft'       => Appointment::where('status', 'draft')->count(),
                'scheduled'   => Appointment::where('status', 'scheduled')->count(),
                'completed'   => Appointment::where('status', 'completed')->count(),
            ],
            'resources' => [
                'total'  => Resource::count(),
                'active' => Resource::where('active', true)->count(),
            ],
            'absences' => [
                'pending'  => Absence::where('status', 'pending')->count(),
                'approved' => Absence::where('status', 'approved')->count(),
            ],
            'recent_work_orders' => WorkOrder::with('creator')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    }
}
