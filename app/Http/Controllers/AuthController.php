<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // Token API (aplikasi mobile) kedaluwarsa setelah 30 hari tidak dipakai:
    // setiap aplikasi dibuka, /auth/refresh memperpanjang masa berlakunya.
    public const TOKEN_TTL_DAYS = 30;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Your account is inactive.'], 403);
        }

        $token = $user->createToken('api-token', ['*'], now()->addDays(self::TOKEN_TTL_DAYS))->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    // Sliding expiry: token yang sama diperpanjang, bukan diganti, supaya
    // respons yang hilang di jalan tidak membuat aplikasi memegang token mati.
    public function refresh(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        $expiresAt = now()->addDays(self::TOKEN_TTL_DAYS);

        if ($token instanceof PersonalAccessToken) {
            $token->forceFill(['expires_at' => $expiresAt])->save();
        }

        return response()->json([
            'user' => $request->user()->load('roles'),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'is_active' => true,
        ]);

        $user->assignRole('client');

        $token = $user->createToken('api-token', ['*'], now()->addDays(self::TOKEN_TTL_DAYS))->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
        ], 201);
    }
}
