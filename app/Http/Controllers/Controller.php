<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Kembalikan company_id tenant saat ini. Null = superadmin (lihat semua). */
    protected function tenantId(): ?int
    {
        $user = auth()->user();
        return ($user && ! $user->is_super_admin) ? $user->company_id : null;
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
