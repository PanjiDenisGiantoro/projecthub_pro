<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
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

    /**
     * Login Google dari aplikasi mobile: aplikasi mengirim ID token hasil
     * Google Sign-In, server memverifikasi tanda tangan + audience-nya lalu
     * menerbitkan token Sanctum. Aturan penautan akun sama dengan login web:
     * cocokkan google_id, atau email yang sudah diverifikasi Google. Email
     * yang belum terdaftar tidak dibuatkan akun otomatis.
     */
    public function google(Request $request)
    {
        $idToken = $request->input('id_token') ?? $request->input('idToken') ?? $request->input('token');

        if (!is_string($idToken) || $idToken === '') {
            throw ValidationException::withMessages([
                'id_token' => ['ID token Google wajib dikirim.'],
            ]);
        }

        $payload = $this->verifyGoogleIdToken($idToken);

        if (!$payload || empty($payload['sub'])) {
            return response()->json(['message' => 'Token Google tidak valid atau sudah kedaluwarsa.'], 401);
        }

        $googleId = (string) $payload['sub'];
        $email = $payload['email'] ?? null;
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            if ($emailVerified && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                return response()->json([
                    'message' => 'Email Google ini belum terdaftar. Silakan daftar terlebih dahulu.',
                    'code' => 'not_registered',
                    'name' => $payload['name'] ?? null,
                    'email' => $email,
                ], 404);
            }

            $user->forceFill(['google_id' => $googleId])->save();
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

    /**
     * Verifikasi ID token terhadap setiap client ID yang diizinkan: client web
     * (GOOGLE_CLIENT_ID) plus GOOGLE_MOBILE_CLIENT_IDS (dipisah koma) bila
     * aplikasi Android memakai client/proyek Firebase yang berbeda.
     */
    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $clientIds = array_filter(array_map('trim', array_merge(
            [(string) config('services.google.client_id')],
            explode(',', (string) config('services.google.mobile_client_ids'))
        )));

        foreach (array_unique($clientIds) as $clientId) {
            try {
                $payload = (new \Google\Client(['client_id' => $clientId]))->verifyIdToken($idToken);
                if (is_array($payload)) {
                    return $payload;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
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

    /**
     * Kirim token reset password ke email. Respons selalu sama, baik email
     * terdaftar atau tidak, supaya endpoint ini tidak bisa dipakai menebak
     * email yang terdaftar. Broker membatasi 1 email per user per 60 detik.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = PasswordBroker::sendResetLink(
            ['email' => $request->email],
            fn (User $user, string $token) => $user->notify(new ResetPasswordToken($token))
        );

        if (! in_array($status, [
            PasswordBroker::RESET_LINK_SENT,
            PasswordBroker::INVALID_USER,
            PasswordBroker::RESET_THROTTLED,
        ], true)) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json([
            'message' => 'Jika email terdaftar, token reset password sudah dikirim. Periksa inbox atau folder spam.',
            'resend_after' => 60,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Keluarkan semua sesi mobile lama setelah password diganti.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => ['Token reset tidak valid atau sudah kedaluwarsa. Minta token baru.'],
            ]);
        }

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login dengan password baru.',
        ]);
    }
}
