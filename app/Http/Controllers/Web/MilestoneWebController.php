<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class MilestoneWebController extends Controller
{
    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'code'         => 'nullable|string|max:50',
            'start_date'   => 'nullable|date',
            'due_date'     => 'nullable|date',
            'assigned_to'  => 'nullable|exists:users,id',
            'priority'     => 'nullable|in:low,medium,high,urgent',
            'release_target' => 'nullable|string|max:100',
            'status'       => 'nullable|in:planning,in_progress,completed,at_risk,pending',
        ]);

        $data = $request->only('title', 'code', 'description', 'start_date', 'due_date', 'status', 'assigned_to', 'priority', 'release_target');
        if (empty($data['priority'])) $data['priority'] = 'low';
        
        $statusMap = [
            'planning'    => 'pending',
            'pending'     => 'pending',
            'in_progress' => 'in_progress',
            'active'      => 'in_progress',
            'at_risk'     => 'in_progress',
            'completed'   => 'completed',
        ];
        $rawStatus = $data['status'] ?? 'pending';
        $data['status'] = $statusMap[$rawStatus] ?? 'pending';

        $milestone = $project->milestones()->create($data);

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForMilestone($milestone, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil ditambahkan.',
                'milestone' => $milestone->load(['assignee', 'tasks', 'sprints']),
            ], 201);
        }

        return back()->with('success', 'Milestone berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, Milestone $milestone, GoogleCalendarService $calendar)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'code'         => 'nullable|string|max:50',
            'assigned_to'  => 'nullable|exists:users,id',
            'priority'     => 'nullable|in:low,medium,high,urgent',
            'release_target' => 'nullable|string|max:100',
            'status'       => 'nullable|in:planning,in_progress,completed,at_risk,pending',
        ]);

        $data = $request->only('title', 'code', 'description', 'start_date', 'due_date', 'status', 'assigned_to', 'priority', 'release_target');
        if (isset($data['status'])) {
            $statusMap = [
                'planning'    => 'pending',
                'pending'     => 'pending',
                'in_progress' => 'in_progress',
                'active'      => 'in_progress',
                'at_risk'     => 'in_progress',
                'completed'   => 'completed',
            ];
            $data['status'] = $statusMap[$data['status']] ?? 'pending';
        }
        $milestone->update($data);

        if ($milestone->wasChanged('due_date') && $milestone->google_event_id) {
            try {
                $calendar->syncMeetingTime($milestone, auth()->user());
            } catch (\Throwable $e) {
                // silent — jadwal Google Calendar tetap yang lama, tidak blokir update milestone
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil diperbarui.',
                'milestone' => $milestone->load(['assignee', 'tasks', 'sprints']),
            ]);
        }

        return back()->with('success', 'Milestone berhasil diperbarui.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Milestone berhasil dihapus.');
    }

    public function assignItems(Request $request, Project $project, Milestone $milestone)
    {
        $request->validate([
            'sprint_ids' => 'nullable|array',
            'sprint_ids.*' => 'exists:sprints,id',
            'task_ids' => 'nullable|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        // Reset all sprints linked to this milestone first
        Sprint::where('milestone_id', $milestone->id)->update(['milestone_id' => null]);
        // Reset all standalone tasks linked to this milestone first
        Task::where('milestone_id', $milestone->id)->whereNull('sprint_id')->update(['milestone_id' => null]);

        // Re-assign selected sprints
        if ($request->sprint_ids) {
            Sprint::whereIn('id', $request->sprint_ids)
                ->where('project_id', $project->id)
                ->update(['milestone_id' => $milestone->id]);

            // Also assign tasks in those sprints to this milestone
            Task::whereIn('sprint_id', $request->sprint_ids)
                ->where('project_id', $project->id)
                ->update(['milestone_id' => $milestone->id]);
        }

        // Re-assign selected standalone tasks
        if ($request->task_ids) {
            Task::whereIn('id', $request->task_ids)
                ->where('project_id', $project->id)
                ->update(['milestone_id' => $milestone->id]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Milestone assignments berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Milestone assignments berhasil diperbarui.');
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
