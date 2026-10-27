<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /** Kembalikan company_id tenant saat ini. Null = superadmin (lihat semua). */
    protected function tenantId(): ?int
    {
        $user = auth()->user();
        if (! $user || $user->is_super_admin) {
            return null;
        }

        // User biasa tanpa company tidak boleh diperlakukan seperti superadmin (data & role global).
        abort_unless($user->company_id, 403, 'Akun belum terhubung ke perusahaan.');

        return $user->company_id;
    }

    /** Abort 403 jika resource bukan milik tenant saat ini. */
    protected function authorizeCompany(int $resourceCompanyId): void
    {
        $cid = $this->tenantId();
        if ($cid !== null && $cid !== $resourceCompanyId) {
            abort(403);
        }
    }
}
