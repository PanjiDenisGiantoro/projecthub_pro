<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $protectedRoles = ['admin', 'manager', 'developer', 'marketing', 'customer'];

    /**
     * Records which company a custom role belongs to, without altering the
     * shared Spatie `roles` table. A role with no row here is a global
     * system role visible to every tenant.
     */
    public function up(): void
    {
        Schema::create('role_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Best-effort backfill: a pre-existing custom role whose assigned
        // users all belong to a single company is attributed to that company.
        $customRoles = DB::table('roles')->whereNotIn('name', $this->protectedRoles)->get();

        foreach ($customRoles as $role) {
            $companyIds = DB::table('model_has_roles')
                ->join('users', 'users.id', '=', 'model_has_roles.model_id')
                ->where('model_has_roles.role_id', $role->id)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->whereNotNull('users.company_id')
                ->distinct()
                ->pluck('users.company_id');

            if ($companyIds->count() === 1) {
                DB::table('role_companies')->insert([
                    'role_id'    => $role->id,
                    'company_id' => $companyIds->first(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_companies');
    }
};
