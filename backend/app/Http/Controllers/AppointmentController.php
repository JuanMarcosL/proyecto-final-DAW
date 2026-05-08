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
            'resource_id'     => 'required|exists:resources,id',
            'scheduled_start' => 'required|date',
            'scheduled_end'   => 'required|date|after:scheduled_start',
            'notes'           => 'nullable|string',
        ]);

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

        $appointment = Appointment::create($request->only([
            'work_order_id', 'resource_id', 'scheduled_start', 'scheduled_end', 'notes'
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
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
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
