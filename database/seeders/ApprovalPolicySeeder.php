<?php

namespace Database\Seeders;

use App\Models\ApprovalPolicy;
use Illuminate\Database\Seeder;

class ApprovalPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            // ─── Ticket Module ────────────────────────────────────────────────
            [
                'module'         => 'ticket',
                'action'         => 'resolve',
                'flow_type'      => 'any_of',
                'approver_roles' => ['manager'],
                'timeout_hours'  => 24,
                'description'    => 'Ticket resolution requires manager sign-off before closing.',
            ],
            [
                'module'         => 'ticket',
                'action'         => 'close',
                'flow_type'      => 'any_of',
                'approver_roles' => ['manager', 'admin'],
                'timeout_hours'  => 48,
                'description'    => 'Ticket closure requires manager or admin confirmation.',
            ],
            [
                'module'         => 'ticket',
                'action'         => 'escalate_priority',
                'flow_type'      => 'single',
                'approver_roles' => ['manager'],
                'timeout_hours'  => 4,
                'description'    => 'Priority escalation to critical/high requires manager approval.',
            ],
            [
                'module'         => 'ticket',
                'action'         => 'sla_extension',
                'flow_type'      => 'single',
                'approver_roles' => ['manager'],
                'timeout_hours'  => 2,
                'description'    => 'SLA due date extension requires manager approval.',
            ],
            [
                'module'         => 'ticket',
                'action'         => 'start_enhancement',
                'flow_type'      => 'sequential',
                'approver_roles' => ['manager', 'admin'],
                'timeout_hours'  => 8,
                'description'    => 'Starting an enhancement ticket requires manager then admin approval.',
            ],
            [
                'module'         => 'ticket',
                'action'         => 'security_disclose',
                'flow_type'      => 'sequential',
                'approver_roles' => ['admin', 'manager'],
                'timeout_hours'  => 1,
                'description'    => 'Security ticket disclosure requires admin then manager sign-off.',
            ],
        ];

        foreach ($policies as $policy) {
            ApprovalPolicy::updateOrCreate(
                ['module' => $policy['module'], 'action' => $policy['action']],
                $policy
            );
        }

        $this->command->info('✅ Seeded ' . count($policies) . ' approval policies.');
    }
}
