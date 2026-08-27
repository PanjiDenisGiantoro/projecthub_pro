<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomFieldDefinitionWebController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $fields = CustomFieldDefinition::forCompany(auth()->user()->company_id)->get();

        return view('custom-fields.index', compact('fields'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'label'       => 'required|string|max:100',
            'type'        => ['required', Rule::in(array_keys(CustomFieldDefinition::TYPES))],
            'options'     => 'nullable|string',
            'is_required' => 'boolean',
        ]);

        $companyId = auth()->user()->company_id;

        // key dipakai sebagai JSON key & nama input form — slug dari label, dan
        // dipastikan unik per company (mis. "Nomor BPJS" & "nomor bpjs" ditambahkan
        // dua kali tetap dapat key berbeda: nomor_bpjs, nomor_bpjs_2).
        $baseKey = Str::slug($data['label'], '_') ?: 'field';
        $key     = $baseKey;
        $suffix  = 2;
        while (CustomFieldDefinition::where('company_id', $companyId)->where('key', $key)->exists()) {
            $key = $baseKey . '_' . $suffix++;
        }

        $options = null;
        if ($data['type'] === 'select') {
            $options = collect(explode(',', (string) $request->input('options', '')))
                ->map(fn ($o) => trim($o))
                ->filter()
                ->values()
                ->all();
        }

        $maxOrder = CustomFieldDefinition::where('company_id', $companyId)->max('sort_order') ?? 0;

        CustomFieldDefinition::create([
            'company_id'  => $companyId,
            'key'         => $key,
            'label'       => $data['label'],
            'type'        => $data['type'],
            'options'     => $options,
            'is_required' => $request->boolean('is_required'),
            'is_active'   => true,
            'sort_order'  => $maxOrder + 1,
        ]);

        return back()->with('success', 'Field kustom "' . $data['label'] . '" berhasil ditambahkan.');
    }

    public function toggle(CustomFieldDefinition $customField)
    {
        $this->authorizeAdmin();
        $this->authorizeOwnership($customField);

        $customField->update(['is_active' => ! $customField->is_active]);

        return back()->with('success', 'Status field diperbarui.');
    }

    public function destroy(CustomFieldDefinition $customField)
    {
        $this->authorizeAdmin();
        $this->authorizeOwnership($customField);

        // Data yang sudah kesimpan di users.custom_fields sengaja dibiarkan (tidak
        // ikut dihapus) — cuma berhenti dirender/divalidasi di form, tidak worth
        // scan seluruh tabel users tiap kali definisi field dihapus.
        $customField->delete();

        return back()->with('success', 'Field kustom dihapus.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('admin') || auth()->user()->is_super_admin, 403);
    }

    private function authorizeOwnership(CustomFieldDefinition $customField): void
    {
        abort_unless($customField->company_id === auth()->user()->company_id, 403);
    }
}
