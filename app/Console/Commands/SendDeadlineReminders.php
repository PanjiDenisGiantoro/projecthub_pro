<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Models\EmailNotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders extends Command
{
    protected $signature   = 'notifications:deadline-reminders';
    protected $description = 'Send email reminders for tasks due in the next 24 hours';

    public function handle(): void
    {
        $tomorrow = now()->addDay()->startOfDay();
        $dayAfter  = now()->addDay()->endOfDay();

        $tasks = Task::with('assignee', 'project')
            ->whereBetween('due_date', [$tomorrow, $dayAfter])
            ->whereNotIn('status', ['done'])
            ->whereNotNull('assigned_to')
            ->whereNull('deleted_at')
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $user = $task->assignee;
            if (!$user || !$user->email) continue;

            // Skip if already sent today
            $alreadySent = \App\Models\EmailNotificationLog::where('type', 'deadline_reminder')
                ->where('notifiable_type', Task::class)
                ->where('notifiable_id', $task->id)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if ($alreadySent) continue;

            try {
                Mail::send('emails.deadline_reminder', ['task' => $task, 'user' => $user], function($m) use ($user, $task) {
                    $m->to($user->email, $user->name)
                      ->subject("[ProjectHub] Deadline besok: {$task->title}");
                });

                \App\Models\EmailNotificationLog::create([
                    'user_id'         => $user->id,
                    'type'            => 'deadline_reminder',
                    'notifiable_type' => Task::class,
                    'notifiable_id'   => $task->id,
                    'email'           => $user->email,
                    'subject'         => "[ProjectHub] Deadline besok: {$task->title}",
                    'status'          => 'sent',
                ]);

                $sent++;
            } catch (\Exception $e) {
                \App\Models\EmailNotificationLog::create([
                    'user_id'         => $user->id,
                    'type'            => 'deadline_reminder',
                    'notifiable_type' => Task::class,
                    'notifiable_id'   => $task->id,
                    'email'           => $user->email,
                    'subject'         => "[ProjectHub] Deadline besok: {$task->title}",
                    'status'          => 'failed',
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} deadline reminder(s).");
    }
}
