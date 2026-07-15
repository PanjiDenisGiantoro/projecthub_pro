<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class SyncUserPackagesWithCompanyAdmin extends Command
{
    protected $signature   = 'users:sync-packages-with-admin {--company= : Hanya sync company_id ini}';
    protected $description = 'Samakan package tiap user dengan package admin di company yang sama (union jika admin > 1)';

    public function handle(): void
    {
        $companies = Company::when(
            $this->option('company'),
            fn($q) => $q->where('id', $this->option('company'))
        )->get();

        foreach ($companies as $company) {
            $adminPackageIds = User::where('company_id', $company->id)
                ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
                ->with('packages')
                ->get()
                ->pluck('packages')
                ->flatten()
                ->pluck('id')
                ->unique();

            if ($adminPackageIds->isEmpty()) {
                $this->warn("Company {$company->id} ({$company->name}): tidak ada admin / admin tanpa package, dilewati.");
                continue;
            }

            $users = User::where('company_id', $company->id)
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get();

            foreach ($users as $user) {
                $before = $user->packages->pluck('slug')->sort()->values();
                $user->packages()->sync($adminPackageIds);
                $after = $user->packages()->pluck('slug')->sort()->values();

                if ($before->all() !== $after->all()) {
                    $this->info("  {$user->email}: [{$before->implode(',')}] -> [{$after->implode(',')}]");
                }
            }

            $this->info("Company {$company->id} ({$company->name}) selesai disinkron.");
        }
    }
}
