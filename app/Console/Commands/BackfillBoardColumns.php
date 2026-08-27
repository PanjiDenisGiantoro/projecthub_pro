<?php

namespace App\Console\Commands;

use App\Models\BoardColumnTemplate;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBoardColumns extends Command
{
    protected $signature = 'board-columns:backfill {--dry-run : Report what would happen without committing}';

    protected $description = 'Clone the default board-column template into every project missing columns, then link existing tasks to their matching column';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run — no changes will be committed.');
        }

        DB::beginTransaction();

        $this->call('db:seed', ['--class' => 'BoardColumnTemplateSeeder', '--force' => true]);

        $template = BoardColumnTemplate::default();

        if (! $template) {
            DB::rollBack();
            $this->error('Default "Kanban Dasar" template not found even after seeding — aborting.');

            return self::FAILURE;
        }

        $projectsBackfilled = 0;
        Project::whereDoesntHave('boardColumns')->chunkById(100, function ($projects) use ($template, &$projectsBackfilled) {
            foreach ($projects as $project) {
                $template->applyTo($project);
                $projectsBackfilled++;
            }
        });

        $tasksBackfilled = 0;
        $tasksUnmatched = 0;
        Task::whereNull('board_column_id')->chunkById(500, function ($tasks) use (&$tasksBackfilled, &$tasksUnmatched) {
            foreach ($tasks as $task) {
                $column = $task->project?->boardColumns->firstWhere('slug', $task->status);

                if (! $column) {
                    $this->warn("No matching column for task #{$task->id} (project #{$task->project_id}, status \"{$task->status}\") — skipped.");
                    $tasksUnmatched++;

                    continue;
                }

                $task->update(['board_column_id' => $column->id]);
                $tasksBackfilled++;
            }
        });

        $this->table(
            ['Metric', 'Count'],
            [
                ['Projects backfilled with default columns', $projectsBackfilled],
                ['Tasks linked to a board column', $tasksBackfilled],
                ['Tasks left unmatched (needs manual review)', $tasksUnmatched],
            ]
        );

        if ($dryRun) {
            DB::rollBack();
            $this->info('Dry run complete — rolled back, nothing was committed.');
        } else {
            DB::commit();
            $this->info('Backfill complete.');
        }

        return self::SUCCESS;
    }
}
