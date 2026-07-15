<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature   = 'superadmin:set {email}';
    protected $description = 'Set or unset a user as super admin by email';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error("User dengan email {$this->argument('email')} tidak ditemukan.");
            return;
        }

        $user->update(['is_super_admin' => !$user->is_super_admin]);

        $status = $user->is_super_admin ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        $this->info("Super admin untuk {$user->name} ({$user->email}): {$status}");
    }
}
