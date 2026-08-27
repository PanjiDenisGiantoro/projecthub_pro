<?php

namespace App\Services;

use App\Models\BugTicket;
use App\Models\GoogleToken;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Notifications\MeetingCreatedNotification;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    /**
     * @throws \RuntimeException jika user belum menghubungkan akun Google
     */
    public function client(User $user): Client
    {
        $token = $user->googleToken;

        if (!$token) {
            throw new \RuntimeException('Akun Google belum terhubung. Silakan hubungkan di halaman Profil terlebih dahulu.');
        }

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken([
            'access_token'  => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'expires_in'    => $token->expires_at ? max(0, now()->diffInSeconds($token->expires_at, false)) : 0,
        ]);

        if ($client->isAccessTokenExpired()) {
            if (!$token->refresh_token) {
                throw new \RuntimeException('Sesi Google Calendar kedaluwarsa. Silakan hubungkan ulang akun Google Anda di halaman Profil.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);

            if (isset($newToken['error'])) {
                throw new \RuntimeException('Sesi Google Calendar kedaluwarsa. Silakan hubungkan ulang akun Google Anda di halaman Profil.');
            }

            $token->update([
                'access_token' => $newToken['access_token'],
                'expires_at'   => now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);

            $client->setAccessToken($newToken + ['refresh_token' => $token->refresh_token]);
        }

        return $client;
    }

    public function createMeetingForSprint(Sprint $sprint, User $actor): array
    {
        $attendees = $this->attendeesFor($sprint);
        [$start, $end] = $this->resolveMeetingWindow($sprint->project, $this->resolveAnchorDate($sprint));

        return $this->createEvent(
            project: $sprint->project,
            actor: $actor,
            summary: 'Sprint: '.$sprint->name,
            description: $sprint->goal ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $sprint->update([
                'google_event_id'             => $eventId,
                'google_meet_link'            => $meetLink,
                'meeting_starts_at'           => $start,
                'google_meeting_organizer_id' => $actor->id,
            ]),
        );
    }

    public function createRecurringMeetingForSprint(Sprint $sprint, User $actor): array
    {
        $attendees = $this->attendeesFor($sprint);
        $anchor    = $this->resolveAnchorDate($sprint);
        [$start, $end] = $this->resolveMeetingWindow($sprint->project, $anchor);

        $untilDate = $sprint->end_date ? Carbon::parse($sprint->end_date) : $anchor->copy()->addWeeks(4);
        $until     = $untilDate->copy()->endOfDay()->utc()->format('Ymd\THis\Z');

        return $this->createEvent(
            project: $sprint->project,
            actor: $actor,
            summary: 'Daily Standup: '.$sprint->name,
            description: $sprint->goal ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $sprint->update([
                'google_event_id'             => $eventId,
                'google_meet_link'            => $meetLink,
                'meeting_starts_at'           => $start,
                'google_meeting_organizer_id' => $actor->id,
                'google_meeting_is_recurring' => true,
            ]),
            recurrence: ['RRULE:FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;UNTIL='.$until],
        );
    }

    public function createMeetingForMilestone(Milestone $milestone, User $actor): array
    {
        $attendees = $this->attendeesFor($milestone);
        [$start, $end] = $this->resolveMeetingWindow($milestone->project, $this->resolveAnchorDate($milestone));

        return $this->createEvent(
            project: $milestone->project,
            actor: $actor,
            summary: 'Milestone: '.$milestone->title,
            description: $milestone->description ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $milestone->update([
                'google_event_id'             => $eventId,
                'google_meet_link'            => $meetLink,
                'meeting_starts_at'           => $start,
                'google_meeting_organizer_id' => $actor->id,
            ]),
        );
    }

    public function createMeetingForTask(Task $task, User $actor): array
    {
        $attendees = $this->attendeesFor($task);
        [$start, $end] = $this->resolveMeetingWindow($task->project, $this->resolveAnchorDate($task));

        return $this->createEvent(
            project: $task->project,
            actor: $actor,
            summary: 'Task: '.$task->title,
            description: $task->description ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $task->update([
                'google_event_id'             => $eventId,
                'google_meet_link'            => $meetLink,
                'meeting_starts_at'           => $start,
                'google_meeting_organizer_id' => $actor->id,
            ]),
        );
    }

    public function createMeetingForBugTicket(BugTicket $ticket, User $actor): array
    {
        $attendees = $this->attendeesFor($ticket);
        [$start, $end] = $this->resolveMeetingWindow($ticket->project, $this->resolveAnchorDate($ticket));

        return $this->createEvent(
            project: $ticket->project,
            actor: $actor,
            summary: 'Tiket: '.$ticket->title,
            description: $ticket->description ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $ticket->update([
                'google_event_id'   => $eventId,
                'google_meet_link'  => $meetLink,
                'meeting_starts_at' => $start,
            ]),
        );
    }

    public function createMeetingForProject(Project $project, User $actor): array
    {
        $attendees = $this->attendeesFor($project);
        [$start, $end] = $this->resolveMeetingWindow($project, $this->resolveAnchorDate($project));

        return $this->createEvent(
            project: $project,
            actor: $actor,
            summary: 'Meeting: '.$project->name,
            description: $project->description ?? '',
            start: $start,
            end: $end,
            attendees: $attendees,
            onCreated: fn (string $eventId, string $meetLink) => $project->update([
                'google_event_id'   => $eventId,
                'google_meet_link'  => $meetLink,
                'meeting_starts_at' => $start,
            ]),
        );
    }

    /**
     * Sinkronkan ulang jam meeting di Google Calendar setelah tanggal anchor
     * (start_date/due_date) entity berubah. No-op kalau belum ada meeting,
     * Google Meet dinonaktifkan, atau (khusus Sprint) meeting-nya recurring
     * standup — update RRULE di luar scope fitur ini.
     */
    public function syncMeetingTime(Sprint|Milestone|Task $entity, User $actor): void
    {
        if (!$entity->google_event_id) {
            return;
        }

        if ($entity instanceof Sprint && $entity->google_meeting_is_recurring) {
            return;
        }

        $project = $entity->project;

        if (!$project->google_meet_enabled) {
            return;
        }

        [$start, $end] = $this->resolveMeetingWindow($project, $this->resolveAnchorDate($entity));

        $organizer = $entity->meetingOrganizer ?? $actor;
        $client    = $this->client($organizer);
        $service   = new Calendar($client);

        $event = new Event([
            'start' => new EventDateTime(['dateTime' => $start->toRfc3339String(), 'timeZone' => config('app.timezone')]),
            'end'   => new EventDateTime(['dateTime' => $end->toRfc3339String(), 'timeZone' => config('app.timezone')]),
        ]);

        $service->events->patch('primary', $entity->google_event_id, $event, ['sendUpdates' => 'all']);

        $entity->update(['meeting_starts_at' => $start]);
    }

    /** @return array<string, User> keyed by email untuk dedupe, sesuai tipe entity */
    public function attendeesFor(Sprint|Milestone|Task|BugTicket|Project $entity): array
    {
        if ($entity instanceof Sprint) {
            $entity->loadMissing('project.client', 'project.manager', 'project.members.user');
            return $this->resolveAttendees($entity->project);
        }

        if ($entity instanceof Milestone) {
            $entity->loadMissing('project.client', 'project.manager', 'project.members.user', 'assignee');
            $attendees = $this->resolveAttendees($entity->project);
            if ($entity->assignee) {
                $attendees[$entity->assignee->email] = $entity->assignee;
            }
            return $attendees;
        }

        if ($entity instanceof Task) {
            $entity->loadMissing('project.client', 'project.manager', 'project.members.user', 'assignee');
            $attendees = $this->resolveAttendees($entity->project);
            if ($entity->assignee) {
                $attendees[$entity->assignee->email] = $entity->assignee;
            }
            return $attendees;
        }

        if ($entity instanceof BugTicket) {
            $entity->loadMissing('project.client', 'project.manager', 'project.members.user', 'reporter', 'assignee');
            $attendees = $this->resolveAttendees($entity->project);
            if ($entity->reporter) {
                $attendees[$entity->reporter->email] = $entity->reporter;
            }
            if ($entity->assignee) {
                $attendees[$entity->assignee->email] = $entity->assignee;
            }
            return $attendees;
        }

        $entity->loadMissing('client', 'manager', 'members.user');
        return $this->resolveAttendees($entity);
    }

    /** Tanggal anchor untuk hitung jam meeting, sesuai tipe entity. */
    protected function resolveAnchorDate(Sprint|Milestone|Task|BugTicket|Project $entity): Carbon
    {
        if ($entity instanceof Sprint) {
            return $entity->start_date ? Carbon::parse($entity->start_date) : now()->addDay();
        }

        if ($entity instanceof Milestone) {
            return $entity->due_date ? Carbon::parse($entity->due_date) : now()->addDay();
        }

        if ($entity instanceof Task) {
            if ($entity->due_date) {
                return Carbon::parse($entity->due_date);
            }
            return $entity->start_date ? Carbon::parse($entity->start_date) : now()->addDay();
        }

        if ($entity instanceof BugTicket) {
            return $entity->sla_due_at ? Carbon::parse($entity->sla_due_at) : now()->addDay();
        }

        return now()->addDay();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function resolveMeetingWindow(Project $project, Carbon $anchor): array
    {
        $time = $project->meeting_default_time
            ? Carbon::parse($project->meeting_default_time)
            : Carbon::createFromTime(9, 0);

        $start = $anchor->copy()->setTime((int) $time->format('H'), (int) $time->format('i'));
        $end   = $start->copy()->addMinutes($project->meeting_default_duration_minutes ?: 60);

        return [$start, $end];
    }

    /** @return array<string, User> keyed by email untuk dedupe */
    protected function resolveAttendees($project): array
    {
        $attendees = [];

        foreach ($project->members as $member) {
            if ($member->user && $member->user->email) {
                $attendees[$member->user->email] = $member->user;
            }
        }

        if ($project->client && $project->client->email) {
            $attendees[$project->client->email] = $project->client;
        }

        if ($project->manager && $project->manager->email) {
            $attendees[$project->manager->email] = $project->manager;
        }

        return $attendees;
    }

    /** @param array<string, User> $attendees */
    protected function createEvent(Project $project, User $actor, string $summary, string $description, Carbon $start, Carbon $end, array $attendees, \Closure $onCreated, ?array $recurrence = null): array
    {
        if (!$project->google_meet_enabled) {
            throw new \RuntimeException('Google Meet dinonaktifkan untuk project ini. Aktifkan di halaman Edit Project.');
        }

        $client   = $this->client($actor);
        $service  = new Calendar($client);

        $eventData = [
            'summary'     => $summary,
            'description' => $description,
            'start'       => new EventDateTime(['dateTime' => $start->toRfc3339String(), 'timeZone' => config('app.timezone')]),
            'end'         => new EventDateTime(['dateTime' => $end->toRfc3339String(), 'timeZone' => config('app.timezone')]),
            'attendees'   => array_values(array_map(
                fn (User $u) => new EventAttendee(['email' => $u->email, 'displayName' => $u->name]),
                $attendees
            )),
            'conferenceData' => new ConferenceData([
                'createRequest' => new CreateConferenceRequest([
                    'requestId'            => (string) \Illuminate\Support\Str::uuid(),
                    'conferenceSolutionKey' => new ConferenceSolutionKey(['type' => 'hangoutsMeet']),
                ]),
            ]),
        ];

        if ($recurrence) {
            $eventData['recurrence'] = $recurrence;
        }

        $event = new Event($eventData);

        $created = $service->events->insert('primary', $event, [
            'conferenceDataVersion' => 1,
            'sendUpdates'           => 'all',
        ]);

        $meetLink = $created->getHangoutLink() ?? '';

        $onCreated($created->getId(), $meetLink);

        foreach ($attendees as $user) {
            if ($user->id !== $actor->id) {
                $user->notify(new MeetingCreatedNotification($summary, $start, $meetLink));
            }
        }

        return ['event_id' => $created->getId(), 'meet_link' => $meetLink];
    }
}
