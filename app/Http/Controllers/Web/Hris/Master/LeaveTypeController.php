<?php

namespace App\Http\Controllers\Web\Hris\Master;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Database\Seeders\LeaveTypeSeeder;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create hris master');
        $request->validate([
            'name'             => 'required|string|max:100',
            'code'             => 'required|string|max:30',
            'default_quota'    => 'required|integer|min:0',
            'is_paid'          => 'boolean',
            'needs_attachment' => 'boolean',
            'needs_approval'   => 'boolean',
            'has_balance'      => 'boolean',
            'gender_restriction' => 'required|in:all,male,female',
        ]);

        LeaveType::create([
            ...$request->only(['name','code','description','default_quota','gender_restriction']),
            'company_id'       => auth()->user()->company_id,
            'is_paid'          => $request->boolean('is_paid'),
            'needs_attachment' => $request->boolean('needs_attachment'),
            'needs_approval'   => $request->boolean('needs_approval', true),
            'has_balance'      => $request->boolean('has_balance', true),
            'is_active'        => true,
        ]);

        return redirect()->route('hris.master.index', ['tab' => 'leave-types'])->with('success', 'Jenis cuti ditambahkan.');
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $this->authorize('update hris master');
        $leaveType->update([
            ...$request->only(['name','description','default_quota','gender_restriction']),
            'is_paid'          => $request->boolean('is_paid'),
            'needs_attachment' => $request->boolean('needs_attachment'),
            'needs_approval'   => $request->boolean('needs_approval', true),
            'has_balance'      => $request->boolean('has_balance', true),
        ]);

        return redirect()->route('hris.master.index', ['tab' => 'leave-types'])->with('success', 'Jenis cuti diperbarui.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $this->authorize('delete hris master');
        $leaveType->delete();
        return redirect()->route('hris.master.index', ['tab' => 'leave-types'])->with('success', 'Jenis cuti dihapus.');
    }

    public function toggle(LeaveType $leaveType)
    {
        $this->authorize('update hris master');
        $leaveType->update(['is_active' => !$leaveType->is_active]);
        return back()->with('success', 'Status diperbarui.');
    }

    public function resetDefault()
    {
        $this->authorize('create hris master');
        $companyId = auth()->user()->company_id;

        LeaveType::whereNull('company_id')->get()->each(function ($t) use ($companyId) {
            $data = collect($t->toArray())
                ->except(['id', 'company_id', 'created_at', 'updated_at'])
                ->toArray();

            LeaveType::updateOrCreate(
                ['company_id' => $companyId, 'code' => $t->code],
                $data
            );
        });

        return redirect()->route('hris.master.index', ['tab' => 'leave-types'])->with('success', 'Reset ke default berhasil.');
    }
}
