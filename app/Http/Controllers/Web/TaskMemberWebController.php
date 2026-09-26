<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskMemberWebController extends Controller
{
    public function add(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('company_id', $project->company_id)],
        ]);

        $task->members()->syncWithoutDetaching([$request->user_id]);

        $user = User::find($request->user_id);

        if (function_exists('activity') && $user) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties([
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                ])
                ->log('member_added');
        }

        return response()->json([
            'ok'   => true,
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar_url ?? null,
            ],
        ]);
    }

    public function remove(Project $project, Task $task, User $user)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $task->members()->detach($user->id);

        if (function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->withProperties([
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                ])
                ->log('member_removed');
        }

        return response()->json(['ok' => true]);
    }
}
