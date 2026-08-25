<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites role-name strings stored as DATA in approval_policies.approver_roles
 * (json array) and approval_steps.approver_role (string) so existing approval
 * routing keeps matching after roles are consolidated to admin/member/client.
 */
return new class extends Migration
{
    private array $map = [
        'manager' => 'member',
        'developer' => 'member',
        'marketing' => 'member',
        'customer' => 'client',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach ($this->map as $old => $new) {
                DB::table('approval_steps')->where('approver_role', $old)->update(['approver_role' => $new]);
            }

            DB::table('approval_policies')->get(['id', 'approver_roles'])->each(function ($row) {
                $roles = json_decode($row->approver_roles, true) ?? [];
                $roles = array_values(array_unique(array_map(fn ($r) => $this->map[$r] ?? $r, $roles)));
                DB::table('approval_policies')->where('id', $row->id)
                    ->update(['approver_roles' => json_encode($roles), 'updated_at' => now()]);
            });
        });
    }

    public function down(): void
    {
        // Not reversible: 'member' cannot be unambiguously mapped back to
        // manager/developer/marketing — the information is lost by the merge
        // itself (same limitation as the role-consolidation migration).
    }
};
