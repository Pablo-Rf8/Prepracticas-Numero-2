<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'], 'is_admin' => ['prohibited'],
        ]);

        return $this->tokenResponse($request, User::create($data), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Las credenciales son incorrectas.']]);
        }

        return $this->tokenResponse($request, $user);
    }

    private function tokenResponse(Request $request, User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('postman', ['*'], now()->addHours(8))->plainTextToken;
        $request->setUserResolver(fn () => $user);

        return response()->json(['data' => new UserResource($user), 'token' => $token, 'token_type' => 'Bearer', 'expires_in' => 28800], $status);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
