<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\DobleFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está desactivada.'],
            ]);
        }

        // El doble factor también cierra la puerta de la API: la contraseña sola
        // no emite un token a quien lo tiene activo.
        if ($user->tieneDobleFactor()) {
            $codigo = $request->input('codigo');

            if (! is_string($codigo) || ! app(DobleFactorService::class)->verificar($user, $codigo)) {
                throw ValidationException::withMessages([
                    'codigo' => ['Falta el código del doble factor o es incorrecto.'],
                ]);
            }
        } elseif (config('sistema.doble_factor_admin_obligatorio') && $user->role?->tienePermiso(Role::TODO)) {
            return response()->json(['error' => 'Activá el doble factor desde el panel web antes de usar la API.'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->nombre ?? null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('role');

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role->nombre ?? null,
        ]);
    }
}
