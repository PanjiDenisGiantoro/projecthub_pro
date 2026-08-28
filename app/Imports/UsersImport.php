<?php

namespace App\Imports;

use App\Models\CustomFieldDefinition;
use App\Models\OrganizationUnit;
use App\Models\Package;
use App\Models\StructuralLevel;
use App\Models\User;
use App\Support\EmploymentType;
use App\Support\RoleLabel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Spatie\Permission\Models\Role;

/**
 * Import data karyawan dari Excel, di-scope ke satu company. Baris dengan email
 * yang sudah terdaftar (di company yang sama) meng-update user tersebut secara
 * parsial — kolom yang dikosongkan tidak menimpa data yang sudah ada. Baris
 * dengan email baru membuat user baru dengan password acak (admin harus minta
 * karyawan reset password lewat "lupa password").
 */
class UsersImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public int $created = 0;
    public int $updated = 0;

    /** @var array<int,string> */
    public array $errors = [];

    private Collection $customFields;
    private Collection $allowedRoles;
    private Collection $organizationUnits;
    private Collection $structuralLevels;
    private Collection $companyPackageIds;

    public function __construct(private int $companyId)
    {
        $this->customFields = CustomFieldDefinition::forCompany($companyId)->active()->get();
        $this->allowedRoles = Role::whereNotIn('name', ['client', 'tester'])->get();
        $this->organizationUnits = OrganizationUnit::where('company_id', $companyId)->get();
        $this->structuralLevels = StructuralLevel::where('company_id', $companyId)->get();
        $this->companyPackageIds = Package::whereHas('users', fn ($q) => $q->where('company_id', $companyId))->pluck('id');
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 heading row, +1 karena index 0-based

            $name  = trim((string) ($row['nama'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if ($name === '' && $email === '') {
                continue; // baris kosong, lewati diam-diam
            }

            if ($name === '' || $email === '') {
                $this->errors[] = "Baris {$line}: Nama dan Email wajib diisi.";
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Baris {$line}: Email \"{$email}\" tidak valid.";
                continue;
            }

            $roleRaw = trim((string) ($row['role'] ?? ''));
            $role    = $roleRaw !== '' ? $this->matchRole($roleRaw) : null;
            if ($roleRaw !== '' && ! $role) {
                $this->errors[] = "Baris {$line}: Role \"{$roleRaw}\" tidak dikenali.";
                continue;
            }

            $existing = User::where('email', $email)->first();

            if (! $existing && ! $role) {
                $this->errors[] = "Baris {$line}: Role wajib diisi untuk user baru.";
                continue;
            }

            if ($existing && $existing->company_id !== $this->companyId) {
                $this->errors[] = "Baris {$line}: Email \"{$email}\" sudah dipakai user di perusahaan lain.";
                continue;
            }

            $orgRaw = trim((string) ($row['departemen_unit'] ?? ''));
            $organizationUnitId = null;
            if ($orgRaw !== '') {
                $organizationUnitId = $this->matchByName($this->organizationUnits, $orgRaw);
                if (! $organizationUnitId) {
                    $this->errors[] = "Baris {$line}: Departemen/Unit \"{$orgRaw}\" tidak ditemukan.";
                    continue;
                }
            }

            $levelRaw = trim((string) ($row['level_struktural'] ?? ''));
            $structuralLevelId = null;
            if ($levelRaw !== '') {
                $structuralLevelId = $this->matchByName($this->structuralLevels, $levelRaw);
                if (! $structuralLevelId) {
                    $this->errors[] = "Baris {$line}: Level Struktural \"{$levelRaw}\" tidak ditemukan.";
                    continue;
                }
            }

            $data = ['name' => $name];

            if ($orgRaw !== '' || ! $existing) {
                $data['organization_unit_id'] = $organizationUnitId;
            }
            if ($levelRaw !== '' || ! $existing) {
                $data['structural_level_id'] = $structuralLevelId;
            }

            $employmentTypeRaw = trim((string) ($row['tipe_karyawan'] ?? ''));
            if ($employmentTypeRaw !== '' || ! $existing) {
                $type = $employmentTypeRaw !== '' ? $this->matchEmploymentType($employmentTypeRaw) : EmploymentType::TETAP;
                $data['employment_type'] = $type;
                $data['employment_type_other'] = EmploymentType::requiresCustomLabel($type)
                    ? (trim((string) ($row['tipe_karyawan_lainnya'] ?? '')) ?: null)
                    : null;
                $data['outsourcing_company_name'] = EmploymentType::requiresSourceCompany($type)
                    ? (trim((string) ($row['nama_perusahaan_asal'] ?? '')) ?: null)
                    : null;
                $data['contract_end_date'] = EmploymentType::allowsContractEndDate($type)
                    ? $this->parseDate($row['tanggal_akhir_kontrak'] ?? null)
                    : null;
            }

            $hireDateRaw = trim((string) ($row['tanggal_bergabung'] ?? ''));
            if ($hireDateRaw !== '' || ! $existing) {
                $data['hire_date'] = $this->parseDate($hireDateRaw);
            }

            $statusRaw = trim((string) ($row['status_aktif'] ?? ''));
            if ($statusRaw !== '' || ! $existing) {
                $data['is_active'] = $this->parseBool($statusRaw, true);
            }

            if ($this->customFields->isNotEmpty()) {
                $data['custom_fields'] = $this->collectCustomFieldValues($row, $existing);
            }

            if ($existing) {
                $existing->update($data);
                if ($role) {
                    $existing->syncRoles([$role->name]);
                }
                $this->updated++;
            } else {
                $data['email']      = $email;
                $data['company_id'] = $this->companyId;
                $data['password']   = Str::random(12);

                $user = User::create($data);
                $user->assignRole($role->name);
                $user->packages()->sync($this->companyPackageIds);
                $this->created++;
            }
        }
    }

    private function matchRole(string $value): ?Role
    {
        return $this->allowedRoles->first(fn ($r) => strcasecmp($r->name, $value) === 0
            || strcasecmp(RoleLabel::for($r->name), $value) === 0);
    }

    private function matchEmploymentType(string $value): string
    {
        foreach (EmploymentType::LABELS as $key => $label) {
            if (strcasecmp($key, $value) === 0 || strcasecmp($label, $value) === 0) {
                return $key;
            }
        }

        return EmploymentType::TETAP;
    }

    private function matchByName(Collection $items, string $value): ?int
    {
        return $items->first(fn ($item) => strcasecmp($item->name, $value) === 0)?->id;
    }

    private function parseDate(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseBool(string $value, bool $default): bool
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $default;
        }

        return in_array($value, ['aktif', 'ya', 'yes', '1', 'true'], true);
    }

    /** @return array<string,mixed> */
    private function collectCustomFieldValues(Collection $row, ?User $existing): array
    {
        $values = $existing?->custom_fields ?? [];

        foreach ($this->customFields as $field) {
            $slug = Str::slug($field->label, '_');
            if (! $row->has($slug)) {
                continue;
            }

            $raw = trim((string) $row[$slug]);
            if ($raw === '' && $existing) {
                continue; // kosong pas update = jangan timpa nilai yang sudah ada
            }

            $values[$field->key] = $field->type === 'checkbox'
                ? $this->parseBool($raw, false)
                : ($raw === '' ? null : $raw);
        }

        return $values;
    }
}
