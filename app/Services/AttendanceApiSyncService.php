<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceApiSource;
use App\Models\AttendanceApiSyncLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Menarik data absensi dari API eksternal yang dikonfigurasi lewat
 * AttendanceApiSource, memetakan field response ke kolom `attendances`
 * (mengikuti pola matching email+tanggal dari AttendancesImport), lalu
 * meng-upsert ke DB kalau mode "apply" — atau cuma menghasilkan preview
 * kalau mode "preview" (dipakai saat auto_insert dimatikan admin).
 */
class AttendanceApiSyncService
{
    /**
     * @return array{log: AttendanceApiSyncLog, rows: array}
     */
    public function sync(AttendanceApiSource $source, string $dateFrom, string $dateTo, bool $apply, ?int $triggeredBy = null): array
    {
        $log = new AttendanceApiSyncLog([
            'attendance_api_source_id' => $source->id,
            'triggered_by' => $triggeredBy,
            'mode' => $apply ? 'apply' : 'preview',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => 'failed',
        ]);

        $preview = [];

        try {
            $params = $this->renderTemplate($source->request_template ?? [], $dateFrom, $dateTo, $source->company_id);
            $log->request_payload = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $body = $this->call($source, $params);
            $log->response_payload = Str::limit(json_encode($body, JSON_UNESCAPED_SLASHES), 20000, ' …(terpotong)');

            $rows = $this->extractRows($body, $source->response_data_path);
            $log->rows_fetched = count($rows);

            $mapping = $source->response_mapping ?? [];
            $created = $updated = $skipped = $failed = 0;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    $failed++;
                    continue;
                }

                $mapped = $this->mapRow($row, $mapping);
                $matchValue = $mapped['match_value'] ?? '';

                if ($matchValue === '' || $matchValue === null) {
                    $failed++;
                    $preview[] = ['raw' => $row, 'error' => 'Field pencocokan karyawan kosong.'];
                    continue;
                }

                $employee = $this->resolveEmployee($source->company_id, $mapping['match_field'] ?? 'email', $matchValue);

                $item = [
                    'match_value' => $matchValue,
                    'employee'    => $employee ? ['id' => $employee->id, 'name' => $employee->name, 'email' => $employee->email] : null,
                    'date'        => $mapped['date'],
                    'data'        => $mapped['data'],
                ];

                if (! $employee) {
                    $failed++;
                    $item['result'] = 'error';
                    $item['error'] = "Karyawan \"{$matchValue}\" tidak ditemukan di perusahaan ini.";
                    $preview[] = $item;
                    continue;
                }

                if (! $mapped['date']) {
                    $failed++;
                    $item['result'] = 'error';
                    $item['error'] = 'Tanggal kosong atau tidak bisa diparse.';
                    $preview[] = $item;
                    continue;
                }

                $existing = Attendance::where('user_id', $employee->id)->where('date', $mapped['date'])->first();

                if ($apply) {
                    if ($existing && ! $source->overwrite_on_conflict) {
                        $skipped++;
                        $item['result'] = 'skipped';
                    } elseif ($existing) {
                        $existing->update($mapped['data']);
                        $updated++;
                        $item['result'] = 'updated';
                    } else {
                        Attendance::create([
                            'user_id'    => $employee->id,
                            'company_id' => $source->company_id,
                            'date'       => $mapped['date'],
                            'status'     => $mapped['data']['status'] ?? 'hadir',
                            ...$mapped['data'],
                        ]);
                        $created++;
                        $item['result'] = 'created';
                    }
                } else {
                    $item['result'] = $existing
                        ? ($source->overwrite_on_conflict ? 'will_update' : 'will_skip')
                        : 'will_create';
                }

                $preview[] = $item;
            }

            $log->rows_created = $created;
            $log->rows_updated = $updated;
            $log->rows_skipped = $skipped;
            $log->rows_failed = $failed;
            $log->status = $failed > 0
                ? (($created + $updated + $skipped) > 0 ? 'partial' : 'failed')
                : 'success';
        } catch (\Throwable $e) {
            $log->error_message = Str::limit($e->getMessage(), 2000);
            $log->status = 'failed';
        }

        $log->save();

        return ['log' => $log, 'rows' => $preview];
    }

    /** Test koneksi & lihat bentuk response mentah — dipakai admin saat menyusun mapping, tidak menyentuh DB. */
    public function testFetch(AttendanceApiSource $source, string $dateFrom, string $dateTo): array
    {
        $params = $this->renderTemplate($source->request_template ?? [], $dateFrom, $dateTo, $source->company_id);
        $body = $this->call($source, $params);

        return ['params' => $params, 'response' => $body];
    }

    private function renderTemplate(mixed $template, string $dateFrom, string $dateTo, int $companyId): mixed
    {
        $replacements = [
            '{{date}}'       => $dateFrom,
            '{{date_from}}'  => $dateFrom,
            '{{date_to}}'    => $dateTo,
            '{{company_id}}' => (string) $companyId,
        ];

        $walk = function ($value) use (&$walk, $replacements) {
            if (is_array($value)) {
                return array_map($walk, $value);
            }

            return is_string($value) ? strtr($value, $replacements) : $value;
        };

        return $walk($template ?? []);
    }

    private function call(AttendanceApiSource $source, array $params): array
    {
        $request = Http::timeout(20);

        switch ($source->auth_type) {
            case 'bearer':
                $request = $request->withToken($source->auth_config['token'] ?? '');
                break;
            case 'api_key':
                $request = $request->withHeaders([
                    ($source->auth_config['header'] ?? 'X-API-Key') => $source->auth_config['value'] ?? '',
                ]);
                break;
            case 'basic':
                $request = $request->withBasicAuth(
                    $source->auth_config['username'] ?? '',
                    $source->auth_config['password'] ?? ''
                );
                break;
        }

        $response = strtolower($source->method) === 'post'
            ? $request->asJson()->post($source->url, $params)
            : $request->get($source->url, $params);

        if ($response->failed()) {
            throw new \RuntimeException("API merespons status {$response->status()}: " . Str::limit($response->body(), 300));
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            throw new \RuntimeException('Response API bukan JSON array/objek yang valid.');
        }

        return $decoded;
    }

    private function extractRows(array $body, ?string $path): array
    {
        $data = $path ? Arr::get($body, $path) : $body;

        if ($data === null) {
            throw new \RuntimeException("Path response \"{$path}\" tidak ditemukan di response API.");
        }

        if (! is_array($data)) {
            throw new \RuntimeException("Path response \"{$path}\" bukan array/list data.");
        }

        return array_is_list($data) ? $data : [$data];
    }

    private function mapRow(array $row, array $mapping): array
    {
        $matchValue = Arr::get($row, $mapping['match_value'] ?? '');
        $dateRaw = Arr::get($row, $mapping['date'] ?? '');

        $data = [];
        foreach (['check_in', 'check_out', 'check_in_2', 'check_out_2'] as $field) {
            $path = $mapping[$field] ?? null;
            $value = $path ? Arr::get($row, $path) : null;
            if ($value !== null && $value !== '') {
                $data[$field] = $this->parseTime((string) $value);
            }
        }
        foreach (['status', 'notes'] as $field) {
            $path = $mapping[$field] ?? null;
            $value = $path ? Arr::get($row, $path) : null;
            if ($value !== null && $value !== '') {
                $data[$field] = $value;
            }
        }

        return [
            'match_value' => $matchValue,
            'date'        => $dateRaw !== null && $dateRaw !== '' ? $this->parseDate((string) $dateRaw) : null,
            'data'        => $data,
        ];
    }

    private function resolveEmployee(int $companyId, string $matchField, string $matchValue): ?User
    {
        $matchField = $matchField === 'id' ? 'id' : 'email';
        $value = $matchField === 'email' ? strtolower(trim($matchValue)) : trim($matchValue);

        return User::where('company_id', $companyId)->where($matchField, $value)->first();
    }

    private function parseDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(string $value): ?string
    {
        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
