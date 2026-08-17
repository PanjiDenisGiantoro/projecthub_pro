<?php

namespace App\Console\Commands;

use App\Models\BugTicket;
use App\Models\Milestone;
use App\Models\PhNotification;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    protected $signature   = 'meetings:send-reminders';
    protected $description = 'Kirim notifikasi in-app H-1 dan menjelang mulai untuk meeting Google Meet yang terjadwal';

    protected array $types = [Project::class, Sprint::class, Milestone::class, Task::class, BugTicket::class];

    public function handle(GoogleCalendarService $calendar, NotificationService $notifier): void
    {
        $sent = 0;

        foreach ($this->types as $type) {
            $sent += $this->remindStage($type, $calendar, $notifier, 'meeting_reminder_1day', now()->addHours(23), now()->addHours(25), fn ($m) => $this->labelFor($m)." akan berlangsung besok jam {$m->meeting_starts_at->format('H:i')}.");
            $sent += $this->remindStage($type, $calendar, $notifier, 'meeting_reminder_soon', now()->addMinutes(45), now()->addMinutes(75), fn ($m) => $this->labelFor($m)." akan segera dimulai, sekitar jam {$m->meeting_starts_at->format('H:i')}.");
        }

        $this->info("Sent {$sent} meeting reminder notification(s).");
    }

    protected function remindStage(string $type, GoogleCalendarService $calendar, NotificationService $notifier, string $stage, $from, $to, \Closure $message): int
    {
        $entities = $type::whereNotNull('google_meet_link')
            ->whereNotNull('meeting_starts_at')
            ->whereBetween('meeting_starts_at', [$from, $to])
            ->get();

        $sent = 0;

        foreach ($entities as $entity) {
            $attendees = $calendar->attendeesFor($entity);

            foreach ($attendees as $user) {
                $alreadySent = PhNotification::where('user_id', $user->id)
                    ->where('type', $stage)
                    ->where('data->meetable_type', $type)
                    ->where('data->meetable_id', $entity->id)
                    ->exists();

                if ($alreadySent) continue;

                $notifier->send(
                    $user->id,
                    $stage,
                    'Pengingat Meeting',
                    $message($entity),
                    ['meetable_type' => $type, 'meetable_id' => $entity->id]
                );

                $sent++;
            }
        }

        return $sent;
    }

    protected function labelFor($entity): string
    {
        return match (true) {
            $entity instanceof Sprint     => 'Sprint "'.$entity->name.'"',
            $entity instanceof Milestone  => 'Milestone "'.$entity->title.'"',
            $entity instanceof Task       => 'Task "'.$entity->title.'"',
            $entity instanceof BugTicket  => 'Tiket "'.$entity->title.'"',
            $entity instanceof Project    => 'Meeting proyek "'.$entity->name.'"',
            default                       => 'Meeting',
        };
    }
}
