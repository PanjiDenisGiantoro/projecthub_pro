<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tanda centang selesai di task — terpisah dari status/bucket.
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
        });

        // Task yang sudah selesai tetap tampil tercentang.
        DB::table('tasks')
            ->where(function ($q) {
                $q->whereIn('status', ['done', 'completed'])
                  ->orWhereIn('board_column_id', DB::table('board_columns')->where('is_done', true)->select('id'));
            })
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
