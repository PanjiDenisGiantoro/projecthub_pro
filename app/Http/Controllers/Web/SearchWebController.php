<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\CustomerRequest;
use App\Models\KbArticle;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class SearchWebController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return view('search.index', ['query' => $q, 'results' => null]);
        }

        $like = "%{$q}%";

        $tasks = Task::where(fn($sq) => $sq->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->with('project', 'assignee')
            ->limit(20)->get();

        $projects = Project::where(fn($sq) => $sq->where('name', 'like', $like)->orWhere('description', 'like', $like))
            ->with('manager')
            ->limit(10)->get();

        $tickets = BugTicket::where(fn($sq) => $sq->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->with('project')
            ->limit(10)->get();

        $requests = CustomerRequest::where(fn($sq) => $sq->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->with('project')
            ->limit(10)->get();

        $articles = KbArticle::where(fn($sq) => $sq->where('title', 'like', $like)->orWhere('body', 'like', $like))
            ->with('project')
            ->limit(10)->get();

        $results = compact('tasks', 'projects', 'tickets', 'requests', 'articles');
        $totalCount = collect($results)->sum(fn($r) => $r->count());

        return view('search.index', compact('results', 'totalCount') + ['query' => $q]);
    }
}
