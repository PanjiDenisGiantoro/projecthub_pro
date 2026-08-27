<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use App\Models\User;
use App\Support\SystemRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

// ApprovalPolicySeeder called via $this->call()

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        foreach (SystemRoles::ALL as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create default users
        $admin = User::updateOrCreate(
            ['email' => 'admin@projecthub.pro'],
            ['name' => 'Admin ProjectHub', 'password' => 'password', 'is_active' => true, 'timezone' => 'Asia/Jakarta']
        );
        $admin->syncRoles(['admin']);

        $member = User::updateOrCreate(
            ['email' => 'member@projecthub.pro'],
            ['name' => 'Member One', 'password' => 'password', 'is_active' => true, 'timezone' => 'Asia/Jakarta']
        );
        $member->syncRoles(['member']);

        $client = User::updateOrCreate(
            ['email' => 'client@projecthub.pro'],
            ['name' => 'Client One', 'password' => 'password', 'is_active' => true, 'timezone' => 'Asia/Jakarta']
        );
        $client->syncRoles(['client']);

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

        // Seed available packages
        $this->call(PackageSeeder::class);

        // Seed HRIS master data
        $this->call(LeaveTypeSeeder::class);
        $this->call(OvertimeRuleSeeder::class);
        $this->call(TaxConfigSeeder::class);

        // Seed default structural level templates (company_id null)
        $this->call(StructuralLevelSeeder::class);

        // Seed the default board-column (Kanban) template (company_id null)
        $this->call(BoardColumnTemplateSeeder::class);

        $this->command->info('✅ Seeded roles, users, default SLA policies, and approval policies.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['admin',  'admin@projecthub.pro',  'password'],
                ['member', 'member@projecthub.pro', 'password'],
                ['client', 'client@projecthub.pro', 'password'],
            ]
        );
    }
}
