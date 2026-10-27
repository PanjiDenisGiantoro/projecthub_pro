<?php

use App\Support\PricingCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga paket jadi per user (FLOVIG PRICE): price = per user / bulan (tagihan bulanan),
 * price_yearly = per user / bulan kalau ditagih tahunan (disc 30%). Kuota & platform per paket.
 * Paket lama diganti nama (ID dipertahankan supaya order/pelanggan lama tetap tersambung):
 * basic → starter, standard → team, premium → business.
 */
return new class extends Migration
{
    private array $renames = ['basic' => 'starter', 'standard' => 'team', 'premium' => 'business'];

    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('price_yearly')->nullable()->after('price'); // per user / bulan, ditagih tahunan
            $table->unsignedInteger('min_users')->nullable()->after('duration_days');
            $table->unsignedInteger('max_workspaces')->nullable()->after('max_users'); // null = unlimited
            $table->unsignedInteger('max_projects')->nullable()->after('max_workspaces'); // null = unlimited
            $table->string('storage_label', 30)->nullable()->after('max_projects');
            $table->boolean('has_android')->default(true)->after('storage_label');
            $table->boolean('has_ios')->default(true)->after('has_android');
            $table->string('support_label')->nullable()->after('has_ios');
        });

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('billing_cycle', 10)->default('monthly')->after('package_name'); // monthly | yearly
            $table->unsignedInteger('user_count')->nullable()->after('billing_cycle');
        });

        foreach ($this->renames as $old => $new) {
            if (! DB::table('packages')->where('slug', $new)->exists()) {
                DB::table('packages')->where('slug', $old)->update(['slug' => $new]);
            }
        }

        foreach (PricingCatalog::tiers() as $slug => $data) {
            DB::table('packages')->where('slug', $slug)->update($data + ['updated_at' => now()]);
        }

        foreach (PricingCatalog::features() as $slug => $labels) {
            $id = DB::table('packages')->where('slug', $slug)->value('id');
            if (! $id) {
                continue;
            }
            DB::table('package_features')->where('package_id', $id)->delete();
            foreach ($labels as $i => $label) {
                DB::table('package_features')->insert([
                    'package_id' => $id, 'label' => $label, 'sort_order' => $i,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('packages')->where('slug', $new)->update(['slug' => $old]);
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'user_count']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['price_yearly', 'min_users', 'max_workspaces', 'max_projects', 'storage_label', 'has_android', 'has_ios', 'support_label']);
        });
    }
};
