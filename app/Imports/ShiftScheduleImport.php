<?php

namespace App\Imports;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Upload jadwal shift bulanan dari template grid (lihat ShiftScheduleTemplateSheet):
 * baris 1 = periode "YYYY-MM", baris 2 = header tanggal, baris 3+ = Email | Nama | kode per tanggal.
 * Kode: nama shift -> override shift, LIBUR -> override libur, DEFAULT -> hapus override,
 * kosong -> tidak diubah. Baris/sel yang tidak valid dilewati & dilaporkan di $errors,
 * sisanya tetap disimpan (sama seperti AttendancesImport).
 */
class ShiftScheduleImport
{
    public const CODE_OFF     = 'LIBUR';
    public const CODE_DEFAULT = 'DEFAULT';

    private const OFF_ALIASES = ['LIBUR', 'L', 'OFF'];

    public ?Carbon $period = null;
    public int $saved   = 0;
    public int $reset   = 0;

    /** @var array<int,string> */
    public array $errors = [];

    public function __construct(private int $companyId) {}

    /** @param array<int,array<int,mixed>> $rows Isi sheet pertama (tanpa heading row). */
    public function import(array $rows): void
    {
        $this->period = $this->parsePeriod($rows[0][1] ?? null);
        if (!$this->period) {
            $this->errors[] = 'Periode di sel B1 tidak valid (format YYYY-MM). Gunakan template dari tombol Download Template.';
            return;
        }

        // Kolom tanggal dari header baris 2: "1 Sel", "2 Rab", ... -> [indexKolom => tanggal].
        $dateColumns = [];
        foreach (array_slice($rows[1] ?? [], 2, null, true) as $col => $label) {
            if (preg_match('/^\s*(\d{1,2})\b/', (string) $label, $m) && (int) $m[1] >= 1 && (int) $m[1] <= $this->period->daysInMonth) {
                $dateColumns[$col] = $this->period->copy()->day((int) $m[1])->toDateString();
            }
        }
        if (!$dateColumns) {
            $this->errors[] = 'Header tanggal di baris 2 tidak ditemukan. Gunakan template dari tombol Download Template.';
            return;
        }

        $employees = User::where('company_id', $this->companyId)
            ->where('is_super_admin', false)
            ->get(['id', 'email'])
            ->keyBy(fn ($u) => strtolower($u->email));

        $shifts = Shift::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->keyBy(fn ($s) => mb_strtolower(trim($s->name)));

        DB::transaction(function () use ($rows, $dateColumns, $employees, $shifts) {
            foreach (array_slice($rows, 2, null, true) as $index => $row) {
                $line  = $index + 1;
                $email = strtolower(trim((string) ($row[0] ?? '')));
                $cells = array_intersect_key($row, $dateColumns);

                if ($email === '') {
                    if (collect($cells)->filter(fn ($v) => trim((string) $v) !== '')->isNotEmpty()) {
                        $this->errors[] = "Baris {$line}: Email wajib diisi.";
                    }
                    continue;
                }

                $employee = $employees->get($email);
                if (!$employee) {
                    $this->errors[] = "Baris {$line}: email \"{$email}\" tidak ditemukan di perusahaan ini.";
                    continue;
                }

                foreach ($cells as $col => $value) {
                    $code = trim((string) $value);
                    if ($code === '') {
                        continue;
                    }

                    $date  = $dateColumns[$col];
                    $upper = mb_strtoupper($code);

                    if ($upper === self::CODE_DEFAULT) {
                        $this->reset += ShiftSchedule::where('user_id', $employee->id)->whereDate('date', $date)->delete();
                        continue;
                    }

                    if (in_array($upper, self::OFF_ALIASES, true)) {
                        $shiftId = null;
                    } elseif ($shift = $shifts->get(mb_strtolower($code))) {
                        $shiftId = $shift->id;
                    } else {
                        $this->errors[] = "Baris {$line} tgl " . Carbon::parse($date)->day . ": kode \"{$code}\" bukan nama shift aktif / LIBUR / DEFAULT.";
                        continue;
                    }

                    ShiftSchedule::updateOrCreate(
                        ['user_id' => $employee->id, 'date' => $date],
                        ['company_id' => $this->companyId, 'shift_id' => $shiftId]
                    );
                    $this->saved++;
                }
            }
        });
    }

    private function parsePeriod(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Kalau Excel sempat mengubah "2026-09" jadi tanggal, nilainya berupa serial number.
        if (is_numeric($value) && (float) $value > 1000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfMonth();
        }

        $value = trim((string) $value);
        if (!preg_match('/^(\d{4})-(\d{1,2})/', $value, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
    }
}
