<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $currentUser = $request->user();

        $allowedRoles = $currentUser->role === 'admin'
            ? ['admin', 'supervisor', 'tecnico']
            : ['tecnico'];

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:' . implode(',', $allowedRoles),
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'role'     => $request->role,
            'active'   => true,
        ]);

        if ($request->role === 'tecnico') {
            \App\Models\Resource::create([
                'user_id'   => $user->id,
                'specialty' => 'Por definir',
                'zone'      => 'Por definir',
                'active'    => true,
            ]);
        }

        // Generar token de reset password y enviar email
        $token = Password::createToken($user);
        $resetUrl = config('app.frontend_url', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        $user->notify(new WelcomeNotification($resetUrl));

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->role === 'supervisor' && $user->role !== 'tecnico') {
            return response()->json(['message' => 'No tienes permiso para editar este usuario.'], 403);
        }

        $allowedRoles = $currentUser->role === 'admin'
            ? ['admin', 'supervisor', 'tecnico']
            : ['tecnico'];

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role'     => 'sometimes|in:' . implode(',', $allowedRoles),
            'active'   => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'email', 'role', 'active']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo.'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
