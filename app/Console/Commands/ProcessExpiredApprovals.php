<?php

namespace App\Console\Commands;

use App\Services\ApprovalService;
use Illuminate\Console\Command;

class ProcessExpiredApprovals extends Command
{
    protected $signature   = 'approvals:expire';
    protected $description = 'Expire pending approvals that have passed their timeout';

    public function handle(ApprovalService $approvalService): void
    {
        $count = $approvalService->processExpired();
        $this->info("Expired {$count} approval(s).");
    }
}
