<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
// ApprovalPolicySeeder called via $this->call()

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = ['admin', 'manager', 'marketing', 'developer', 'customer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create default users
        $admin = User::factory()->create([
            'name' => 'Admin ProjectHub',
            'email' => 'admin@projecthub.pro',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $manager = User::factory()->create([
            'name' => 'Manager One',
            'email' => 'manager@projecthub.pro',
            'is_active' => true,
        ]);
        $manager->assignRole('manager');

        $dev = User::factory()->create([
            'name' => 'Developer One',
            'email' => 'dev@projecthub.pro',
            'is_active' => true,
        ]);
        $dev->assignRole('developer');

        $marketing = User::factory()->create([
            'name' => 'Marketing One',
            'email' => 'marketing@projecthub.pro',
            'is_active' => true,
        ]);
        $marketing->assignRole('marketing');

        $customer = User::factory()->create([
            'name' => 'Client One',
            'email' => 'client@projecthub.pro',
            'is_active' => true,
        ]);
        $customer->assignRole('customer');

        // Default SLA policies (global)
        $slaPolicies = [
            ['priority' => 'critical', 'response_minutes' => 30,   'resolution_minutes' => 240,   'escalation_at_percent' => 75],
            ['priority' => 'high',     'response_minutes' => 120,  'resolution_minutes' => 1440,  'escalation_at_percent' => 75],
            ['priority' => 'medium',   'response_minutes' => 240,  'resolution_minutes' => 4320,  'escalation_at_percent' => 75],
            ['priority' => 'low',      'response_minutes' => 1440, 'resolution_minutes' => 10080, 'escalation_at_percent' => 75],
        ];

        foreach ($slaPolicies as $policy) {
            SlaPolicy::firstOrCreate(
                ['project_id' => null, 'priority' => $policy['priority']],
                [...$policy, 'created_by' => $admin->id, 'business_hours_only' => false]
            );
        }

        // Seed permissions & role assignments
        $this->call(PermissionSeeder::class);

        // Seed default approval policies
        $this->call(ApprovalPolicySeeder::class);

        $this->command->info('✅ Seeded roles, users, default SLA policies, and approval policies.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['admin',     'admin@projecthub.pro',     'password'],
                ['manager',   'manager@projecthub.pro',   'password'],
                ['developer', 'dev@projecthub.pro',       'password'],
                ['marketing', 'marketing@projecthub.pro', 'password'],
                ['customer',  'client@projecthub.pro',    'password'],
            ]
        );
    }
}
