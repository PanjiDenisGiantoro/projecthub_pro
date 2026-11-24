<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Profil milik user yang sedang login (aplikasi mobile).
 * Padanan API dari Web\ProfileWebController.
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['data' => $this->payload($request->user())]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:50', 'timezone'],
            'email_notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('email_notifications_enabled', $validated)) {
            $validated['email_notifications_enabled'] = $request->boolean('email_notifications_enabled');
        }

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Informasi profil berhasil diperbarui.',
            'data'    => $this->payload($user->fresh()),
        ]);
    }

    // multipart/form-data, field "avatar". Pakai POST (PHP tidak mem-parse file di PUT).
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif,webp'],
        ]);

        $user = $request->user();
        $this->deleteAvatarFile($user);

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui.',
            'data'    => $this->payload($user->fresh()),
        ]);
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        $this->deleteAvatarFile($user);
        $user->update(['avatar' => null]);

        return response()->json([
            'message' => 'Foto profil berhasil dihapus.',
            'data'    => $this->payload($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::defaults()],
        ], [
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = $request->user();

        // Cek manual: rule `current_password` bergantung pada guard default,
        // tidak andal untuk request token Sanctum.
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini salah.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        // Token di perangkat lain dicabut; token yang sedang dipakai tetap hidup.
        $currentId = optional($user->currentAccessToken())->id;
        $user->tokens()->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))->delete();

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    private function deleteAvatarFile(User $user): void
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    private function payload(User $user): array
    {
        $user->loadMissing('roles:id,name');

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'avatar'      => $user->avatar,
            'avatar_url'  => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
            'initials'    => $user->initials(),
            'timezone'    => $user->timezone,
            'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'company_id'  => $user->company_id,
            'roles'       => $user->roles->pluck('name')->values(),
            'has_google'  => (bool) $user->google_id,
            'created_at'  => $user->created_at?->toIso8601String(),
        ];
    }
}
