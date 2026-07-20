<?php

namespace App\Services;

use App\Models\RecurringTaskDefinition;
use App\Models\Task;
use Illuminate\Support\Carbon;

class RecurringTaskGenerator
{
    /** Dipanggil scheduler harian: generate semua definisi aktif yang jadwalnya cocok hari ini. */
    public function generateDue(): int
    {
        $today = now()->toImmutable();
        $count = 0;

        foreach (RecurringTaskDefinition::where('is_active', true)->get() as $def) {
            if ($this->generateOne($def, $today)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate satu task dari satu definisi. $force=true (dipakai tombol "Generate Sekarang")
     * melewati pengecekan jadwal (hari ini cocok atau tidak), tapi tetap mencegah generate
     * dobel di hari yang sama.
     */
    public function generateOne(RecurringTaskDefinition $def, ?Carbon $today = null, bool $force = false): ?Task
    {
        $today ??= now()->toImmutable();

        if ($def->last_generated_at && $def->last_generated_at->toDateString() === $today->toDateString()) {
            return null;
        }

        if (! $force && ! $this->matchesSchedule($def, $today)) {
            return null;
        }

        $task = Task::create([
            'project_id'              => $def->project_id,
            'milestone_id'            => $def->milestone_id,
            'title'                   => $def->title,
            'description'             => $def->description,
            'assigned_to'             => $def->assigned_to,
            'status'                  => 'todo',
            'priority'                => $def->priority,
            'start_date'              => $today,
            'due_date'                => $today->addDays($def->due_offset_days),
            'estimated_hours'         => $def->estimated_hours,
            'created_by'              => $def->created_by,
            'recurring_definition_id' => $def->id,
        ]);

        $def->update(['last_generated_at' => $today->toDateString()]);

        return $task;
    }

    private function matchesSchedule(RecurringTaskDefinition $def, Carbon $today): bool
    {
        return match ($def->frequency) {
            'daily'    => true,
            'weekly'   => $today->dayOfWeek === ($def->day_of_week ?? 1),
            'biweekly' => $today->dayOfWeek === ($def->day_of_week ?? 1) && $today->weekOfYear % 2 === 0,
            'monthly'  => $today->day === ($def->day_of_month ?? 1),
            default    => false,
        };
    }
}
