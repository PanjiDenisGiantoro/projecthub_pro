<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('goal_leads')->default(0)->after('leads_count');
            $table->decimal('actual_spend', 15, 2)->default(0)->after('budget');
            $table->unsignedInteger('clicks')->default(0)->after('impressions');
            $table->unsignedInteger('reach')->default(0)->after('clicks');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('email')->nullable()->after('contact');
            $table->string('phone')->nullable()->after('email');
            $table->string('source')->nullable()->after('company'); // website, referral, ads, event, cold_call
            $table->tinyInteger('score')->default(0)->after('source'); // 1-10
            $table->decimal('value', 15, 2)->nullable()->after('score'); // potential deal value
            $table->date('follow_up_at')->nullable()->after('value');
            $table->timestamp('last_contacted_at')->nullable()->after('follow_up_at');
            $table->string('lost_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['description', 'goal_leads', 'actual_spend', 'clicks', 'reach', 'owner_id']);
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'source', 'score', 'value', 'follow_up_at', 'last_contacted_at', 'lost_reason']);
        });
    }
};
