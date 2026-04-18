<?php

namespace App\Http\Controllers;

use App\Models\BugTicket;
use App\Models\TicketChecklist;
use Illuminate\Http\Request;

class TicketChecklistController extends Controller
{
    public function index(BugTicket $ticket)
    {
        return response()->json($ticket->checklists()->with('creator:id,name')->get());
    }

    public function store(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.body' => 'required|string|max:500',
            'items.*.sort_order' => 'integer',
        ]);

        $created = collect($request->items)->map(fn($item, $index) =>
            $ticket->checklists()->create([
                'body' => $item['body'],
                'sort_order' => $item['sort_order'] ?? $index,
                'created_by' => $request->user()->id,
            ])
        );

        return response()->json($created, 201);
    }

    public function update(Request $request, BugTicket $ticket, TicketChecklist $item)
    {
        $this->ensureBelongs($ticket, $item);

        $request->validate(['body' => 'sometimes|string|max:500', 'sort_order' => 'sometimes|integer']);

        $item->update($request->only('body', 'sort_order'));

        return response()->json($item);
    }

    public function toggle(BugTicket $ticket, TicketChecklist $item)
    {
        $this->ensureBelongs($ticket, $item);

        $item->update(['is_done' => !$item->is_done]);

        return response()->json($item);
    }

    public function destroy(BugTicket $ticket, TicketChecklist $item)
    {
        $this->ensureBelongs($ticket, $item);
        $item->delete();
        return response()->json(['message' => 'Item deleted.']);
    }

    private function ensureBelongs(BugTicket $ticket, TicketChecklist $item): void
    {
        abort_if($item->ticket_id !== $ticket->id, 404, 'Checklist item not found on this ticket.');
    }
}
