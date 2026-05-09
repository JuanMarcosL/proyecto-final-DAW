<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Resource;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        return response()->json(
            Appointment::with(['workOrder', 'resource.user'])
                ->orderBy('scheduled_start', 'asc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'work_order_id'   => 'required|exists:work_orders,id',
            'resource_id'     => 'nullable|exists:resources,id',
            'scheduled_start' => 'nullable|date',
            'scheduled_end'   => 'nullable|date|after:scheduled_start',
            'address'         => 'nullable|string',
            'notes'           => 'nullable|string',
            'status' => 'nullable|in:draft,scheduled,in_progress,completed,cancelled',
        ]);

        if ($request->resource_id && $request->scheduled_start && $request->scheduled_end) {
            $overlap = Appointment::where('resource_id', $request->resource_id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($request) {
                    $query->whereBetween('scheduled_start', [$request->scheduled_start, $request->scheduled_end])
                        ->orWhereBetween('scheduled_end', [$request->scheduled_start, $request->scheduled_end]);
                })->exists();

            if ($overlap) {
                return response()->json([
                    'message' => 'El técnico ya tiene una cita en ese horario.'
                ], 422);
            }
        }

        $appointment = Appointment::create($request->only([
            'work_order_id',
            'resource_id',
            'scheduled_start',
            'scheduled_end',
            'address',
            'notes',
            'status'
        ]));

        return response()->json($appointment->load(['workOrder', 'resource.user']), 201);
    }

    public function show(Appointment $appointment)
    {
        return response()->json(
            $appointment->load(['workOrder', 'resource.user'])
        );
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'sometimes|in:draft,scheduled,in_progress,completed,cancelled',
            'notes'  => 'nullable|string',
        ]);

        $appointment->update($request->all());

        return response()->json($appointment->load(['workOrder', 'resource.user']));
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(['message' => 'Cita eliminada correctamente.']);
    }
}
