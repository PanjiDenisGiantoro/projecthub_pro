<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'action' => 'in:start,stop,manual',
            'minutes' => 'required_if:action,manual|nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        if ($request->action === 'start') {
            // Stop any running timer first
            TimeLog::where('user_id', $user->id)->where('is_running', true)->get()->each->stop();

            $log = $task->timeLogs()->create([
                'user_id' => $user->id,
                'started_at' => now(),
                'is_running' => true,
                'notes' => $request->notes,
            ]);

            return response()->json($log, 201);
        }

        if ($request->action === 'stop') {
            $log = $task->timeLogs()
                ->where('user_id', $user->id)
                ->where('is_running', true)
                ->first();

            if (!$log) {
                return response()->json(['message' => 'No running timer found.'], 422);
            }

            $log->stop();
            return response()->json($log);
        }

        // Manual
        $log = $task->timeLogs()->create([
            'user_id' => $user->id,
            'started_at' => now(),
            'ended_at' => now(),
            'minutes' => $request->minutes,
            'notes' => $request->notes,
            'is_running' => false,
        ]);

        return response()->json($log, 201);
    }

    public function timesheet(Request $request, Project $project)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->from, fn($q) => $q->whereDate('started_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('started_at', '<=', $request->to))
            ->get();

        $summary = $logs->groupBy('user_id')->map(function ($userLogs) {
            $user = $userLogs->first()->user;
            return [
                'user' => $user->only(['id', 'name', 'email']),
                'total_minutes' => $userLogs->sum('minutes'),
                'total_hours' => round($userLogs->sum('minutes') / 60, 2),
                'logs_count' => $userLogs->count(),
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'total_minutes' => $logs->sum('minutes'),
            'total_hours' => round($logs->sum('minutes') / 60, 2),
            'logs' => $logs,
        ]);
    }
}
