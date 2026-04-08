<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    protected $signature = 'sla:check';
    protected $description = 'Check for SLA breaches and send warnings';

    public function handle(SlaService $slaService): void
    {
        $this->info('Checking SLA warnings...');
        $slaService->sendWarnings();

        $this->info('Checking SLA breaches...');
        $slaService->checkBreaches();

        $this->info('SLA check complete.');
    }
}
