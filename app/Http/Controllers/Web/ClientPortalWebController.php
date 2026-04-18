<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientPortalToken;
use App\Models\Project;
use Illuminate\Http\Request;

class ClientPortalWebController extends Controller
{
    // Manage tokens (admin/manager)
    public function index(Project $project)
    {
        $tokens = $project->portalTokens()->with('clientUser', 'creator')->orderByDesc('id')->get();
        return view('portal.manage', compact('project', 'tokens'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'label'      => 'nullable|string|max:100',
            'can_comment'=> 'boolean',
            'can_approve'=> 'boolean',
            'show_budget'=> 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $data['project_id'] = $project->id;
        $data['token']      = ClientPortalToken::generateToken();
        $data['created_by'] = auth()->id();

        $token = ClientPortalToken::create($data);

        return back()->with('success', 'Link portal dibuat.')->with('new_token', $token->token);
    }

    public function destroy(Project $project, ClientPortalToken $portalToken)
    {
        $portalToken->delete();
        return back()->with('success', 'Token dicabut.');
    }

    // Public portal (no auth required — token in URL)
    public function view(string $token)
    {
        $pt = ClientPortalToken::where('token', $token)->with('project')->firstOrFail();

        if ($pt->isExpired()) {
            return view('portal.expired');
        }

        $pt->update(['last_accessed_at' => now()]);

        $project = $pt->project->load([
            'milestones' => fn($q) => $q->orderBy('due_date'),
            'tasks' => fn($q) => $q->with('assignee')->limit(50),
            'manager',
        ]);

        return view('portal.view', compact('pt', 'project'));
    }

    public function comment(Request $request, string $token)
    {
        $pt = ClientPortalToken::where('token', $token)->firstOrFail();

        if ($pt->isExpired() || !$pt->can_comment) {
            abort(403);
        }

        // Store as a ticket comment or customer request comment — simplified here
        $request->validate(['message' => 'required|string|max:2000']);

        // For MVP: create a customer request tagged as portal feedback
        $pt->project->customerRequests()->create([
            'title'       => '[Portal Feedback] ' . str($request->message)->limit(80),
            'description' => $request->message,
            'status'      => 'open',
            'type'        => 'feedback',
            'priority'    => 'medium',
        ]);

        return back()->with('success', 'Komentar terkirim.');
    }
}
