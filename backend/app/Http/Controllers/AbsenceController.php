<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'tecnico') {
            $resource = \App\Models\Resource::where('user_id', $user->id)->first();
            if (!$resource) return response()->json([]);

            return response()->json(
                Absence::with(['resource.user', 'resolver'])
                    ->where('resource_id', $resource->id)
                    ->orderBy('created_at', 'desc')
                    ->get()
            );
        }

        return response()->json(
            Absence::with(['resource.user', 'resolver'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'type'        => 'required|in:vacation,medical,personal,other',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);

        $absence = Absence::create($request->only([
            'resource_id',
            'type',
            'start_date',
            'end_date',
            'reason'
        ]));

        return response()->json($absence->load('resource.user'), 201);
    }

    public function show(Absence $absence)
    {
        return response()->json(
            $absence->load(['resource.user', 'resolver'])
        );
    }

    public function update(Request $request, Absence $absence)
    {
        $user = $request->user();

        if ($user->role === 'tecnico') {
            $resource = \App\Models\Resource::where('user_id', $user->id)->first();

            if (!$resource || $absence->resource_id !== $resource->id) {
                return response()->json(['message' => 'No tienes permiso para modificar esta ausencia.'], 403);
            }

            if ($absence->status !== 'pending') {
                return response()->json(['message' => 'Solo puedes modificar ausencias pendientes.'], 422);
            }

            $request->validate([
                'start_date' => 'sometimes|date|after_or_equal:today',
                'end_date'   => 'sometimes|date|after_or_equal:start_date',
                'type'       => 'sometimes|in:vacation,medical,personal,other',
                'reason'     => 'nullable|string',
            ]);

            $absence->update($request->only(['start_date', 'end_date', 'type', 'reason']));
            return response()->json($absence->load(['resource.user', 'resolver']));
        }

        // Supervisor/Admin
        $request->validate([
            'status'     => 'sometimes|in:approved,rejected',
            'type'       => 'sometimes|in:vacation,medical,personal,other',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
            'reason'     => 'nullable|string',
        ]);

        $data = $request->only(['type', 'start_date', 'end_date', 'reason']);
        if ($request->has('status')) {
            $data['status']      = $request->status;
            $data['resolved_by'] = $user->id;
            $data['resolved_at'] = now();
        }

        $absence->update($data);
        return response()->json($absence->load(['resource.user', 'resolver']));
    }

    public function destroy(Absence $absence)
    {
        $user = request()->user();

        if ($user->role === 'tecnico') {
            $resource = \App\Models\Resource::where('user_id', $user->id)->first();

            if (!$resource || $absence->resource_id !== $resource->id) {
                return response()->json(['message' => 'No tienes permiso para eliminar esta ausencia.'], 403);
            }

            if ($absence->status !== 'pending') {
                return response()->json(['message' => 'Solo puedes eliminar ausencias pendientes.'], 422);
            }
        }

        $absence->delete();
        return response()->json(['message' => 'Ausencia eliminada correctamente.']);
    }
}
