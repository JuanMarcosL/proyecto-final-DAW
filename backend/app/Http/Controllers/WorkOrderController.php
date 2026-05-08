<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index()
    {
        return response()->json(
            WorkOrder::with(['creator', 'lines', 'appointments.resource'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'type'        => 'required|in:corrective,preventive,urgent,routine',
            'priority'    => 'required|in:low,medium,high',
            'address'     => 'required|string',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $workOrder = WorkOrder::create([
            ...$request->only(['title', 'description', 'type', 'priority', 'address', 'due_date', 'latitude', 'longitude']),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($workOrder->load(['creator', 'lines']), 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return response()->json(
            $workOrder->load(['creator', 'lines', 'appointments.resource'])
        );
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'type'        => 'sometimes|in:corrective,preventive,urgent,routine',
            'priority'    => 'sometimes|in:low,medium,high',
            'status'      => 'sometimes|in:new,assigned,in_progress,completed,closed',
            'address'     => 'sometimes|string',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $workOrder->update($request->all());

        return response()->json($workOrder->load(['creator', 'lines', 'appointments.resource']));
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();
        return response()->json(['message' => 'Work Order eliminada correctamente.']);
    }
}
