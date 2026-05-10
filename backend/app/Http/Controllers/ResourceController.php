<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index()
    {
        return response()->json(
            Resource::with([
                'user',
                'appointments' => function ($q) {
                    $q->whereNotIn('status', ['completed', 'cancelled']);
                },
                'absences'
            ])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'specialty' => 'required|string|max:255',
            'zone'      => 'required|string|max:255',
            'active'    => 'boolean',
        ]);

        $resource = Resource::create($request->only(['user_id', 'specialty', 'zone', 'active']));

        return response()->json($resource->load('user'), 201);
    }

    public function show(Resource $resource)
    {
        return response()->json(
            $resource->load(['user', 'appointments.workOrder', 'absences'])
        );
    }

    public function update(Request $request, Resource $resource)
    {
        $request->validate([
            'specialty' => 'sometimes|string|max:255',
            'zone'      => 'sometimes|string|max:255',
            'active'    => 'sometimes|boolean',
        ]);

        $resource->update($request->all());

        return response()->json($resource->load('user'));
    }

    public function destroy(Resource $resource)
    {
        $resource->delete();
        return response()->json(['message' => 'Técnico eliminado correctamente.']);
    }
}
