<?php

namespace App\Http\Controllers\Web\Hris;

use App\Http\Controllers\Controller;
use App\Models\AttendanceApiSource;
use App\Services\AttendanceApiSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Konfigurasi & eksekusi sinkronisasi absensi dari API eksternal (mis. mesin
 * fingerprint, HRIS lain) — lihat AttendanceApiSyncService untuk logika
 * fetch + mapping + upsert-nya. Diakses lewat menu "Integrasi API" di
 * halaman Pengaturan Absensi, gated permission 'update absensi' (sama
 * dengan pengaturan absensi lainnya).
 */
class AttendanceApiController extends Controller
{
    public function index()
    {
        $this->authorize('update absensi');
        $companyId = auth()->user()->company_id;

        $sources = AttendanceApiSource::where('company_id', $companyId)
            ->withCount('syncLogs')
            ->orderByDesc('id')
            ->get();

        return view('hris.absensi.integration', compact('sources'));
    }

    public function store(Request $request)
    {
        $this->authorize('update absensi');
        $data = $this->validated($request);

        $source = new AttendanceApiSource();
        $this->fillFromValidated($source, $request, $data);
        $source->save();

        return redirect()->route('hris.absensi.integration.index')->with('success', "Sumber API \"{$source->name}\" berhasil ditambahkan.");
    }

    public function update(Request $request, AttendanceApiSource $source)
    {
        $this->authorize('update absensi');
        $data = $this->validated($request);

        $this->fillFromValidated($source, $request, $data);
        $source->save();

        return redirect()->route('hris.absensi.integration.index')->with('success', "Sumber API \"{$source->name}\" berhasil disimpan.");
    }

    public function destroy(AttendanceApiSource $source)
    {
        $this->authorize('update absensi');
        $name = $source->name;
        $source->delete();

        return redirect()->route('hris.absensi.integration.index')->with('success', "Sumber API \"{$name}\" berhasil dihapus.");
    }

    /** Cek koneksi & lihat bentuk response mentah — dipakai admin saat menyusun mapping, tidak menyentuh DB absensi. */
    public function test(Request $request, AttendanceApiSource $source, AttendanceApiSyncService $service)
    {
        $this->authorize('update absensi');
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        try {
            $result = $service->testFetch($source, $dateFrom, $dateTo);

            return response()->json(['success' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Tarik data dari API. `apply=0` (default) cuma preview, tidak menyentuh DB
     * kecuali auto_insert sumbernya sudah aktif. `apply=1` menulis ke `attendances`
     * (dipakai tombol konfirmasi setelah admin meninjau hasil preview).
     */
    public function sync(Request $request, AttendanceApiSource $source, AttendanceApiSyncService $service)
    {
        $this->authorize('update absensi');
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $apply = $request->boolean('apply') || $source->auto_insert;

        $result = DB::transaction(fn () => $service->sync($source, $dateFrom, $dateTo, $apply, auth()->id()));

        return response()->json([
            'success' => $result['log']->status !== 'failed',
            'applied' => $apply,
            'log'     => $result['log']->only(['status', 'rows_fetched', 'rows_created', 'rows_updated', 'rows_skipped', 'rows_failed', 'error_message']),
            'rows'    => $result['rows'],
        ]);
    }

    public function logs(AttendanceApiSource $source)
    {
        $this->authorize('update absensi');
        $logs = $source->syncLogs()->with('triggeredBy:id,name')->limit(30)->get();

        return response()->json(['logs' => $logs]);
    }

    private function resolveDateRange(Request $request): array
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        return [$request->input('date_from'), $request->input('date_to') ?: $request->input('date_from')];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                    => 'required|string|max:150',
            'method'                  => 'required|in:get,post',
            'url'                     => 'required|string|max:2048',
            'auth_type'               => 'required|in:none,api_key,bearer,basic',
            'auth_api_key_header'     => 'nullable|string|max:100',
            'auth_api_key_value'      => 'nullable|string|max:500',
            'auth_bearer_token'       => 'nullable|string|max:1000',
            'auth_basic_username'     => 'nullable|string|max:150',
            'auth_basic_password'     => 'nullable|string|max:255',
            'request_template'        => 'nullable|json',
            'response_data_path'      => 'nullable|string|max:150',
            'match_field'             => 'required|in:email,id',
            'map_match_value'         => 'required|string|max:150',
            'map_date'                => 'required|string|max:150',
            'map_check_in'            => 'nullable|string|max:150',
            'map_check_out'           => 'nullable|string|max:150',
            'map_check_in_2'          => 'nullable|string|max:150',
            'map_check_out_2'         => 'nullable|string|max:150',
            'map_status'              => 'nullable|string|max:150',
            'map_notes'               => 'nullable|string|max:150',
        ]);
    }

    private function fillFromValidated(AttendanceApiSource $source, Request $request, array $data): void
    {
        $authConfig = match ($data['auth_type']) {
            'api_key' => ['header' => $data['auth_api_key_header'] ?: 'X-API-Key', 'value' => $data['auth_api_key_value'] ?? ''],
            'bearer'  => ['token' => $data['auth_bearer_token'] ?? ''],
            'basic'   => ['username' => $data['auth_basic_username'] ?? '', 'password' => $data['auth_basic_password'] ?? ''],
            default   => null,
        };

        $responseMapping = array_filter([
            'match_field' => $data['match_field'],
            'match_value' => $data['map_match_value'],
            'date'        => $data['map_date'],
            'check_in'    => $data['map_check_in'] ?? null,
            'check_out'   => $data['map_check_out'] ?? null,
            'check_in_2'  => $data['map_check_in_2'] ?? null,
            'check_out_2' => $data['map_check_out_2'] ?? null,
            'status'      => $data['map_status'] ?? null,
            'notes'       => $data['map_notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $source->fill([
            'name'                   => $data['name'],
            'method'                 => $data['method'],
            'url'                    => $data['url'],
            'auth_type'              => $data['auth_type'],
            'auth_config'            => $authConfig,
            'request_template'       => $data['request_template'] ? json_decode($data['request_template'], true) : null,
            'response_data_path'     => $data['response_data_path'] ?: null,
            'response_mapping'       => $responseMapping,
            'auto_insert'            => $request->boolean('auto_insert'),
            'overwrite_on_conflict'  => $request->boolean('overwrite_on_conflict'),
            'is_active'              => $request->boolean('is_active'),
        ]);
    }
}
