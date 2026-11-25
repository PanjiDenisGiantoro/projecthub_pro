<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SlaPolicy;
use App\Services\SlaService;
use Illuminate\Http\Request;

/**
 * Aturan SLA tiket per proyek (tab Tickets → "Aturan SLA Tiket").
 * Disimpan sebagai sla_policies dengan project_id = proyek ini; kalau dihapus
 * (reset), proyek kembali memakai aturan default sistem.
 */
class TicketSlaWebController extends Controller
{
    public function __construct(private SlaService $sla) {}

    /** Yang boleh ubah: punya permission "manage tickets" (admin lewat Gate::before) atau manager proyek. */
    public static function canManage(Project $project): bool
    {
        $user = auth()->user();

        return $user && !$user->hasRole('client')
            && ($user->can('manage tickets') || $project->manager_id === $user->id);
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(self::canManage($project), 403, 'Anda tidak punya akses mengubah aturan SLA proyek ini.');

        $priorities = array_keys(SlaPolicy::PRIORITIES);
        $rules = ['reapply' => 'nullable|boolean'];
        foreach ($priorities as $p) {
            $rules["rules.{$p}.response_minutes"]      = 'required|integer|min:5|max:525600';
            $rules["rules.{$p}.resolution_minutes"]    = 'required|integer|min:15|max:525600';
            $rules["rules.{$p}.escalation_at_percent"] = 'required|integer|min:10|max:100';
            $rules["rules.{$p}.business_hours_only"]   = 'nullable|boolean';
        }
        $validated = $request->validate($rules, [], [
            'rules.*.response_minutes'   => 'target respons',
            'rules.*.resolution_minutes' => 'target selesai',
        ]);

        foreach ($priorities as $p) {
            $r = $validated['rules'][$p];
            if ((int) $r['resolution_minutes'] < (int) $r['response_minutes']) {
                return back()->withErrors(['sla' => 'Target selesai ' . SlaPolicy::PRIORITIES[$p]['label'] . ' tidak boleh lebih cepat dari target responsnya.']);
            }
        }

        foreach ($priorities as $p) {
            $r = $validated['rules'][$p];
            SlaPolicy::updateOrCreate(
                ['project_id' => $project->id, 'priority' => $p],
                [
                    'response_minutes'      => (int) $r['response_minutes'],
                    'resolution_minutes'    => (int) $r['resolution_minutes'],
                    'escalation_at_percent' => (int) $r['escalation_at_percent'],
                    'business_hours_only'   => (bool) ($r['business_hours_only'] ?? false),
                    'created_by'            => auth()->id(),
                ]
            );
        }

        $msg = 'Aturan SLA tiket proyek disimpan.';
        if ($request->boolean('reapply')) {
            $n   = $this->sla->reapplyOpenTickets($project);
            $msg .= " Target {$n} tiket yang masih terbuka ikut dihitung ulang.";
        } else {
            $msg .= ' Berlaku untuk tiket baru & tiket yang prioritasnya diubah.';
        }

        return redirect()->to(route('projects.tab', ['project' => $project, 'tab' => 'tickets']))->with('success', $msg);
    }

    public function reset(Request $request, Project $project)
    {
        abort_unless(self::canManage($project), 403, 'Anda tidak punya akses mengubah aturan SLA proyek ini.');

        SlaPolicy::where('project_id', $project->id)->delete();

        $msg = 'Aturan SLA proyek dikembalikan ke default.';
        if ($request->boolean('reapply')) {
            $n   = $this->sla->reapplyOpenTickets($project);
            $msg .= " Target {$n} tiket yang masih terbuka ikut dihitung ulang.";
        }

        return redirect()->to(route('projects.tab', ['project' => $project, 'tab' => 'tickets']))->with('success', $msg);
    }
}
