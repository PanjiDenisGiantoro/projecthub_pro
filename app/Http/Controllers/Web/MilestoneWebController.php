<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class MilestoneWebController extends Controller
{
    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'start_date'  => 'nullable|date',
            'due_date'    => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        $milestone = $project->milestones()->create($request->only('title', 'description', 'start_date', 'due_date', 'status', 'assigned_to'));

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForMilestone($milestone, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        return back()->with('success', 'Milestone ditambahkan.');
    }

    public function update(Request $request, Project $project, Milestone $milestone, GoogleCalendarService $calendar)
    {
        $request->validate(['assigned_to' => 'nullable|exists:users,id']);
        $milestone->update($request->only('title', 'description', 'start_date', 'due_date', 'status', 'assigned_to'));

        if ($milestone->wasChanged('due_date') && $milestone->google_event_id) {
            try {
                $calendar->syncMeetingTime($milestone, auth()->user());
            } catch (\Throwable $e) {
                // silent — jadwal Google Calendar tetap yang lama, tidak blokir update milestone
            }
        }

        return back()->with('success', 'Milestone diperbarui.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Milestone dihapus.');
    }

    public function createMeeting(Project $project, Milestone $milestone, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForMilestone($milestone, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }
}
