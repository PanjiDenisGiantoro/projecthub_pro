<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BudgetEntry;
use App\Models\Project;
use Illuminate\Http\Request;

class BudgetWebController extends Controller
{
    public function index(Project $project)
    {
        $entries = $project->budgetEntries()->with('creator')->orderByDesc('entry_date')->orderByDesc('id')->get();
        $summary = [
            'budget'   => (float) $project->budget,
            'expenses' => $project->totalExpenses(),
            'income'   => $project->totalIncome(),
            'balance'  => (float) $project->budget - $project->totalExpenses() + $project->totalIncome(),
            'percent'  => $project->budgetUsedPercent(),
        ];
        $byCategory = $entries->where('type', 'expense')
            ->groupBy('category')
            ->map(fn($g) => $g->sum('amount'))
            ->sortByDesc(fn($v) => $v);

        return view('budget.index', compact('project', 'entries', 'summary', 'byCategory'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        BudgetEntry::create($data);

        return back()->with('success', 'Entri anggaran ditambahkan.');
    }

    public function destroy(Project $project, BudgetEntry $budgetEntry)
    {
        $budgetEntry->delete();
        return back()->with('success', 'Entri dihapus.');
    }

    public function updateThreshold(Request $request, Project $project)
    {
        $data = $request->validate(['budget_alert_threshold' => 'nullable|numeric|min:0|max:100']);
        $project->update(['budget_alert_threshold' => $data['budget_alert_threshold']]);
        return back()->with('success', 'Threshold notifikasi diperbarui.');
    }
}
