<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Siapa yang boleh melihat/berinteraksi dengan proyek ini dan semua
     * sub-resource-nya (task, milestone, sprint, budget, risk, kb, file, dll).
     * Global scope company_id di model Project sudah menutup akses lintas
     * perusahaan; policy ini menutup akses lintas proyek DALAM satu
     * perusahaan yang sama (mis. customer/developer yang bukan anggota
     * proyek tersebut).
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return $project->manager_id === $user->id
            || $project->client_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();
    }
}
