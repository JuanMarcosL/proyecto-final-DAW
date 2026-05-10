<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Models\Appointment;

class WorkOrderController extends Controller
{
    public function index()
    {
        return response()->json(
            WorkOrder::with(['creator', 'updater', 'lines', 'appointments.resource'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'type'        => 'nullable|string',
            'priority'    => 'required|in:low,medium,high,critical',
            'address'     => 'nullable|string',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $workOrder = WorkOrder::create([
            ...$request->only(['title', 'description', 'type', 'priority', 'address', 'due_date', 'latitude', 'longitude']),
            'status'     => $request->input('status', 'open'),
            'created_by' => $request->user()->id,
        ]);

        Appointment::create([
            'work_order_id' => $workOrder->id,
            'address' => $request->address,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return response()->json($workOrder->load(['creator', 'lines']), 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return response()->json(
            $workOrder->load(['creator', 'lines', 'appointments.resource.user'])
        );
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'type'        => 'nullable|string',
            'priority'    => 'sometimes|in:low,medium,high,critical',
            'status' => 'nullable|in:open,in_progress,closed,cancelled',
            'address'     => 'nullable|string',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $workOrder->fill($request->all());
        $workOrder->updated_by = auth('sanctum')->id();
        $workOrder->save();

        return response()->json($workOrder->load(['creator', 'updater', 'lines', 'appointments.resource']));
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();
        return response()->json(['message' => 'Work Order eliminada correctamente.']);
    }
}
