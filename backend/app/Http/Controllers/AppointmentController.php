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

        // Verificar que el técnico no tiene ausencia aprobada en ese periodo
        if ($request->resource_id && $request->scheduled_start && $request->scheduled_end) {
            $startDate = date('Y-m-d', strtotime($request->scheduled_start));
            $endDate = date('Y-m-d', strtotime($request->scheduled_end));

            $hasAbsence = \App\Models\Absence::where('resource_id', $request->resource_id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($hasAbsence) {
                return response()->json([
                    'message' => 'El técnico tiene una ausencia aprobada en ese periodo.'
                ], 422);
            }
        }

        // Verificar que el técnico está activo
        $resource = \App\Models\Resource::find($request->resource_id);
        if ($resource && !$resource->active) {
            return response()->json([
                'message' => 'No se puede asignar una cita a un técnico inactivo.'
            ], 422);
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
            'resource_id'     => 'nullable|exists:resources,id',
            'scheduled_start' => 'nullable|date',
            'scheduled_end'   => 'nullable|date|after:scheduled_start',
            'status'          => 'sometimes|in:draft,scheduled,in_progress,completed,cancelled',
            'notes'           => 'nullable|string',
            'address'         => 'nullable|string',
        ]);

        // No se puede cambiar estado de cita completada o cancelada
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'No se puede modificar una cita completada o cancelada.'
            ], 422);
        }

        // No se puede poner en scheduled sin fechas
        $newStatus = $request->input('status', $appointment->status);
        $newStart = $request->input('scheduled_start', $appointment->scheduled_start);
        $newEnd = $request->input('scheduled_end', $appointment->scheduled_end);

        if ($newStatus === 'scheduled' && (!$newStart || !$newEnd)) {
            return response()->json([
                'message' => 'Para programar una cita es necesario indicar fecha de inicio y fin.'
            ], 422);
        }

        // Verificar overlap si hay técnico y fechas
        $resourceId = $request->input('resource_id', $appointment->resource_id);
        if ($resourceId && $newStart && $newEnd) {
            $overlap = Appointment::where('resource_id', $resourceId)
                ->where('id', '!=', $appointment->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($newStart, $newEnd) {
                    $query->whereBetween('scheduled_start', [$newStart, $newEnd])
                        ->orWhereBetween('scheduled_end', [$newStart, $newEnd]);
                })->exists();

            if ($overlap) {
                return response()->json([
                    'message' => 'El técnico ya tiene una cita en ese horario.'
                ], 422);
            }

            // Verificar ausencias aprobadas
            $startDate = date('Y-m-d', strtotime($newStart));
            $endDate = date('Y-m-d', strtotime($newEnd));

            $hasAbsence = \App\Models\Absence::where('resource_id', $resourceId)
                ->where('status', 'approved')
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($hasAbsence) {
                return response()->json([
                    'message' => 'El técnico tiene una ausencia aprobada en ese periodo.'
                ], 422);
            }
        }

        // Verificar que el técnico está activo
        $resource = \App\Models\Resource::find($resourceId);
        if ($resource && !$resource->active) {
            return response()->json([
                'message' => 'No se puede asignar una cita a un técnico inactivo.'
            ], 422);
        }

        $appointment->update($request->only([
            'resource_id',
            'scheduled_start',
            'scheduled_end',
            'status',
            'notes',
            'address'
        ]));

        return response()->json($appointment->load(['workOrder', 'resource.user']));
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(['message' => 'Cita eliminada correctamente.']);
    }
}
