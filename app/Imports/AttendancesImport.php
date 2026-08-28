<?php

namespace App\Imports;

use App\Exports\AttendancesExport;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import data absensi dari Excel, di-scope ke satu company. Baris dicocokkan
 * lewat kombinasi email karyawan + tanggal (unik per Attendance) — kalau sudah
 * ada, di-update parsial (kolom kosong tidak menimpa data yang sudah ada);
 * kalau belum ada, dibuat baris absensi baru. Tidak membuat user baru — email
 * harus milik karyawan yang sudah terdaftar di company ini.
 */
class AttendancesImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public int $created = 0;
    public int $updated = 0;

    /** @var array<int,string> */
    public array $errors = [];

    public function __construct(private int $companyId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 heading row, +1 karena index 0-based

            $email = strtolower(trim((string) ($row['email_karyawan'] ?? '')));
            $dateRaw = trim((string) ($row['tanggal'] ?? ''));

            if ($email === '' && $dateRaw === '') {
                continue; // baris kosong, lewati diam-diam
            }

            if ($email === '') {
                $this->errors[] = "Baris {$line}: Email Karyawan wajib diisi.";
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Baris {$line}: Email \"{$email}\" tidak valid.";
                continue;
            }

            $employee = User::where('email', $email)->where('company_id', $this->companyId)->first();
            if (! $employee) {
                $this->errors[] = "Baris {$line}: Email \"{$email}\" tidak ditemukan di perusahaan ini.";
                continue;
            }

            $date = $this->parseDate($dateRaw);
            if (! $date) {
                $this->errors[] = "Baris {$line}: Tanggal \"{$dateRaw}\" tidak valid atau kosong.";
                continue;
            }

            $existing = Attendance::where('user_id', $employee->id)->where('date', $date)->first();

            $data = [];

            $statusRaw = trim((string) ($row['status'] ?? ''));
            if ($statusRaw !== '') {
                $status = $this->matchStatus($statusRaw);
                if (! $status) {
                    $this->errors[] = "Baris {$line}: Status \"{$statusRaw}\" tidak dikenali.";
                    continue;
                }
                $data['status'] = $status;
            } elseif (! $existing) {
                $data['status'] = 'hadir';
            }

            $checkInRaw = trim((string) ($row['jam_masuk'] ?? ''));
            if ($checkInRaw !== '' || ! $existing) {
                $data['check_in'] = $this->parseTime($checkInRaw);
            }

            $checkOutRaw = trim((string) ($row['jam_keluar'] ?? ''));
            if ($checkOutRaw !== '' || ! $existing) {
                $data['check_out'] = $this->parseTime($checkOutRaw);
            }

            $notesRaw = trim((string) ($row['catatan'] ?? ''));
            if ($notesRaw !== '' || ! $existing) {
                $data['notes'] = $notesRaw !== '' ? $notesRaw : null;
            }

            if ($existing) {
                $existing->update($data);
                $this->updated++;
            } else {
                Attendance::create([
                    'user_id'    => $employee->id,
                    'company_id' => $this->companyId,
                    'date'       => $date,
                    ...$data,
                ]);
                $this->created++;
            }
        }
    }

    private function matchStatus(string $value): ?string
    {
        foreach (AttendancesExport::STATUS_LABELS as $key => $label) {
            if (strcasecmp($key, $value) === 0 || strcasecmp($label, $value) === 0) {
                return $key;
            }
        }

        return null;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i:s');
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
