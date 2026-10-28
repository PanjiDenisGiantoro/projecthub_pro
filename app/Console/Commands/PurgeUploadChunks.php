<?php

namespace App\Console\Commands;

use App\Http\Controllers\Web\TaskAttachmentWebController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeUploadChunks extends Command
{
    protected $signature = 'attachments:purge-chunks {--hours=24}';
    protected $description = 'Delete leftover upload chunks from cancelled or abandoned uploads';

    public function handle(): void
    {
        $disk    = Storage::disk('local');
        $cutoff  = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->directories(TaskAttachmentWebController::CHUNK_DIR) as $userDir) {
            foreach ($disk->directories($userDir) as $uploadDir) {
                // mtime folder berubah setiap ada potongan baru masuk.
                if (filemtime($disk->path($uploadDir)) < $cutoff) {
                    $disk->deleteDirectory($uploadDir);
                    $deleted++;
                }
            }
        }

        $this->info("Deleted {$deleted} abandoned upload(s).");
    }
}
