<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    // Default permissions per role (admin gets everything via Gate::before)
    private array $defaults = [
        'manager' => [
            'access dashboard', 'access projects', 'access tickets', 'access requests',
            'access campaigns', 'access invoices', 'access calendar', 'access search',
            'access templates', 'access workload', 'access analytics', 'access users',
            'access approvals', 'access kb', 'access sprints', 'access budget', 'access risks',
            'create project', 'edit project', 'delete project', 'manage project members',
            'create ticket', 'assign ticket', 'close ticket', 'view all tickets', 'manage tickets',
            'create request', 'approve request',
            'manage campaigns',
            'manage invoices',
            'decide approvals',
            'manage users',
            'manage face enrollment',
        ],
        'developer' => [
            'access dashboard', 'access tickets', 'access requests',
            'access calendar', 'access search', 'access approvals', 'access kb', 'access sprints',
            'create ticket', 'view all tickets', 'manage tickets',
            'decide approvals',
        ],
        'marketing' => [
            'access dashboard', 'access campaigns', 'access requests',
            'access calendar', 'access search', 'access approvals',
            'manage campaigns',
            'decide approvals',
        ],
        'customer' => [
            'access dashboard', 'access tickets', 'access requests',
            'access invoices', 'access approvals', 'access projects',
            'create ticket',
            'create request',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $groups = config('permissions');
        $allPermNames = collect($groups)->flatMap(fn($items) => array_keys($items))->toArray();

        // Create all permissions
        foreach ($allPermNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Assign defaults to non-admin roles
        foreach ($this->defaults as $roleName => $permNames) {
            $role = Role::findByName($roleName, 'web');
            if ($role) {
                $role->syncPermissions(array_intersect($permNames, $allPermNames));
            }
        }

        // Admin gets all permissions (Gate::before in AppServiceProvider handles runtime bypass,
        // but we also sync explicitly for DB consistency)
        $admin = Role::findByName('admin', 'web');
        if ($admin) {
            $admin->syncPermissions($allPermNames);
        }

        $count = count($allPermNames);
        $this->command->info("✅ Seeded {$count} permissions and assigned defaults to all roles.");
    }
}
