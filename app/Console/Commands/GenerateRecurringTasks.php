<?php

namespace App\Console\Commands;

use App\Models\RecurringTaskDefinition;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateRecurringTasks extends Command
{
    protected $signature   = 'tasks:generate-recurring';
    protected $description = 'Generate tasks from recurring task definitions';

    public function handle(): void
    {
        $today = now()->toImmutable();
        $definitions = RecurringTaskDefinition::where('is_active', true)->get();

        $generated = 0;

        foreach ($definitions as $def) {
            if (!$this->shouldGenerate($def, $today)) {
                continue;
            }

            $dueDate = $today->addDays($def->due_offset_days);

            Task::create([
                'project_id'              => $def->project_id,
                'milestone_id'            => $def->milestone_id,
                'title'                   => $def->title,
                'description'             => $def->description,
                'assigned_to'             => $def->assigned_to,
                'status'                  => 'todo',
                'priority'                => $def->priority,
                'start_date'              => $today,
                'due_date'                => $dueDate,
                'estimated_hours'         => $def->estimated_hours,
                'created_by'              => $def->created_by,
                'recurring_definition_id' => $def->id,
            ]);

            $def->update(['last_generated_at' => $today->toDateString()]);
            $generated++;
        }

        $this->info("Generated {$generated} recurring task(s).");
    }

    private function shouldGenerate(RecurringTaskDefinition $def, Carbon $today): bool
    {
        // Don't generate twice on the same day
        if ($def->last_generated_at && $def->last_generated_at->toDateString() === $today->toDateString()) {
            return false;
        }

        return match ($def->frequency) {
            'daily'    => true,
            'weekly'   => $today->dayOfWeek === ($def->day_of_week ?? 1),
            'biweekly' => $today->dayOfWeek === ($def->day_of_week ?? 1) && $today->weekOfYear % 2 === 0,
            'monthly'  => $today->day === ($def->day_of_month ?? 1),
            default    => false,
        };
    }
}
