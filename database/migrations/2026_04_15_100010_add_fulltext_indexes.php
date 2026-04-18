<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tasks ADD FULLTEXT idx_tasks_ft (title, description)');
        DB::statement('ALTER TABLE projects ADD FULLTEXT idx_projects_ft (name, description)');
        DB::statement('ALTER TABLE bug_tickets ADD FULLTEXT idx_tickets_ft (title, description)');
        DB::statement('ALTER TABLE customer_requests ADD FULLTEXT idx_customer_requests_ft (title, description)');
        DB::statement('ALTER TABLE kb_articles ADD FULLTEXT idx_kb_articles_ft (title, body)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tasks DROP INDEX idx_tasks_ft');
        DB::statement('ALTER TABLE projects DROP INDEX idx_projects_ft');
        DB::statement('ALTER TABLE bug_tickets DROP INDEX idx_tickets_ft');
        DB::statement('ALTER TABLE customer_requests DROP INDEX idx_customer_requests_ft');
        DB::statement('ALTER TABLE kb_articles DROP INDEX idx_kb_articles_ft');
    }
};
