<?php

namespace App\Console\Commands;

use App\Services\RecurringTaskGenerator;
use Illuminate\Console\Command;

class GenerateRecurringTasks extends Command
{
    protected $signature   = 'tasks:generate-recurring';
    protected $description = 'Generate tasks from recurring task definitions';

    public function handle(RecurringTaskGenerator $generator): void
    {
        $generated = $generator->generateDue();
        $this->info("Generated {$generated} recurring task(s).");
    }
}
