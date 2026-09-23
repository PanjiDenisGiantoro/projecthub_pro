<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Bonus;
use App\Models\BoardColumnTemplate;
use App\Models\BudgetEntry;
use App\Models\BugTicket;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CustomerRequest;
use App\Models\EmployeeSalary;
use App\Models\Holiday;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Kasbon;
use App\Models\KbArticle;
use App\Models\Lead;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Milestone;
use App\Models\OrganizationUnit;
use App\Models\Overtime;
use App\Models\Package;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Reimbursement;
use App\Models\Risk;
use App\Models\Shift;
use App\Models\ShiftWorkingDay;
use App\Models\SlaPolicy;
use App\Models\Sprint;
use App\Models\StructuralLevel;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data dummy untuk akun panjidenisgiantoroo@gmail.com — mengisi modul
 * Task Management & HRIS pada company milik akun ini dengan data contoh
 * yang realistis (project, task, tiket, payroll, absensi, cuti, dst).
 *
 * Idempotent: aman dijalankan berkali-kali (pakai updateOrCreate/firstOrCreate).
 *
 *   php artisan db:seed --class=Database\\Seeders\\PanjiDummyDataSeeder
 */
class PanjiDummyDataSeeder extends Seeder
{
    private Company $company;

    public function run(): void
    {
        $owner = User::where('email', 'panjidenisgiantoroo@gmail.com')->first();

        if (! $owner) {
            $this->command->error('User panjidenisgiantoroo@gmail.com tidak ditemukan. Jalankan registrasi dulu.');

            return;
        }

        // ── 0. Akun utama: pastikan password, status, role, dan modul sesuai permintaan ──
        $owner->forceFill([
            'password'       => 'W@rung01',
            'is_active'      => true,
            'is_registered'  => true,
            'active_until'   => null, // lifetime, supaya tidak ke-block CheckActiveAccess
            'email_verified_at' => $owner->email_verified_at ?? now(),
        ])->save();

        $owner->assignRole('admin');

        $modulePackages = Package::whereIn('slug', ['hris', 'task_management'])->pluck('id');
        $owner->packages()->syncWithoutDetaching($modulePackages);

        $this->company = Company::findOrFail($owner->company_id);

        // ── 1. Struktur organisasi ────────────────────────────────────────────
        $rootUnit = OrganizationUnit::where('company_id', $this->company->id)->whereNull('parent_id')->first();

        $engineering = $this->orgUnit('Engineering', $rootUnit->id);
        $marketing   = $this->orgUnit('Marketing & Sales', $rootUnit->id);
        $financeHr   = $this->orgUnit('Finance & HR', $rootUnit->id);

        $lvlStaff      = StructuralLevel::whereNull('company_id')->where('name', 'Staff')->first();
        $lvlSupervisor = StructuralLevel::whereNull('company_id')->where('name', 'Supervisor')->first();
        $lvlManager    = StructuralLevel::whereNull('company_id')->where('name', 'Manager')->first();

        // ── 2. Shift kerja ────────────────────────────────────────────────────
        $shiftPagi = Shift::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Shift Pagi'],
            ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'tolerance_minutes' => 15, 'is_active' => true, 'sort_order' => 1]
        );
        $shiftSiang = Shift::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Shift Siang'],
            ['start_time' => '13:00:00', 'end_time' => '22:00:00', 'tolerance_minutes' => 15, 'is_active' => true, 'sort_order' => 2]
        );
        foreach ([$shiftPagi, $shiftSiang] as $shift) {
            foreach (range(1, 5) as $dow) { // Senin–Jumat
                ShiftWorkingDay::firstOrCreate(['shift_id' => $shift->id, 'day_of_week' => $dow]);
            }
        }

        // ── 3. Hari libur nasional 2026 (sample) ─────────────────────────────
        foreach ([
            ['2026-01-01', 'Tahun Baru Masehi'],
            ['2026-03-19', 'Hari Raya Nyepi'],
            ['2026-08-17', 'Hari Kemerdekaan RI'],
            ['2026-12-25', 'Hari Raya Natal'],
        ] as [$date, $name]) {
            Holiday::firstOrCreate(['company_id' => $this->company->id, 'date' => $date], ['name' => $name]);
        }

        // ── 4. Karyawan dummy (HRIS + Task Management) ───────────────────────
        $employeesData = [
            ['key' => 'manager',    'name' => 'Budi Santoso',   'email' => 'budi.santoso@projecthub.pro',   'unit' => $engineering->id, 'level' => $lvlManager->id,    'shift' => $shiftPagi->id,  'role' => 'member', 'salary' => 12000000, 'hire' => '2023-02-01'],
            ['key' => 'dev1',       'name' => 'Siti Amelia',    'email' => 'siti.amelia@projecthub.pro',    'unit' => $engineering->id, 'level' => $lvlStaff->id,      'shift' => $shiftPagi->id,  'role' => 'member', 'salary' => 8500000,  'hire' => '2024-05-10'],
            ['key' => 'dev2',       'name' => 'Andi Wijaya',    'email' => 'andi.wijaya@projecthub.pro',    'unit' => $engineering->id, 'level' => $lvlStaff->id,      'shift' => $shiftSiang->id, 'role' => 'member', 'salary' => 8500000,  'hire' => '2024-08-15'],
            ['key' => 'marketing',  'name' => 'Rina Kartika',   'email' => 'rina.kartika@projecthub.pro',   'unit' => $marketing->id,   'level' => $lvlSupervisor->id, 'shift' => $shiftPagi->id,  'role' => 'member', 'salary' => 9000000,  'hire' => '2023-11-01'],
            ['key' => 'hr',         'name' => 'Dewi Lestari',   'email' => 'dewi.lestari@projecthub.pro',   'unit' => $financeHr->id,   'level' => $lvlStaff->id,      'shift' => $shiftPagi->id,  'role' => 'member', 'salary' => 7500000,  'hire' => '2024-01-20'],
        ];

        $employees = [];
        foreach ($employeesData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'                 => $data['name'],
                    'password'             => 'W@rung01',
                    'company_id'           => $this->company->id,
                    'organization_unit_id' => $data['unit'],
                    'structural_level_id'  => $data['level'],
                    'shift_id'             => $data['shift'],
                    'employment_type'      => 'tetap',
                    'hire_date'            => $data['hire'],
                    'is_active'            => true,
                    'is_registered'        => false,
                    'timezone'             => 'Asia/Jakarta',
                    'email_verified_at'    => now(),
                ]
            );
            $user->syncRoles([$data['role']]);
            $employees[$data['key']] = $user;

            EmployeeSalary::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gaji_pokok'           => $data['salary'],
                    'tunjangan_transport'  => 500000,
                    'tunjangan_makan'      => 500000,
                    'tunjangan_jabatan'    => $data['level'] === $lvlManager->id ? 2000000 : 0,
                    'npwp'                 => null,
                    'status_pajak'         => 'TK/0',
                    'bpjs_kesehatan'       => true,
                    'bpjs_ketenagakerjaan' => true,
                    'effective_date'       => $data['hire'],
                ]
            );
        }

        // Klien dummy buat project (role client)
        $client = User::updateOrCreate(
            ['email' => 'client.demo@projecthub.pro'],
            [
                'name'       => 'PT Klien Demo',
                'password'   => 'W@rung01',
                'company_id' => $this->company->id,
                'is_active'  => true,
                'is_registered' => false,
                'timezone'   => 'Asia/Jakarta',
                'email_verified_at' => now(),
            ]
        );
        $client->syncRoles(['client']);

        $this->seedHris($owner, $employees);
        $this->seedTaskManagement($owner, $employees, $client);

        $this->command->info('');
        $this->command->info('✅ Data dummy Task Management & HRIS selesai dibuat untuk company: ' . $this->company->name);
        $this->command->table(
            ['Nama', 'Email', 'Password', 'Role'],
            [
                [$owner->name, $owner->email, 'W@rung01', 'admin (owner)'],
                ['Budi Santoso', 'budi.santoso@projecthub.pro', 'W@rung01', 'member (manager)'],
                ['Siti Amelia', 'siti.amelia@projecthub.pro', 'W@rung01', 'member (developer)'],
                ['Andi Wijaya', 'andi.wijaya@projecthub.pro', 'W@rung01', 'member (developer)'],
                ['Rina Kartika', 'rina.kartika@projecthub.pro', 'W@rung01', 'member (marketing)'],
                ['Dewi Lestari', 'dewi.lestari@projecthub.pro', 'W@rung01', 'member (HR/finance)'],
                ['PT Klien Demo', 'client.demo@projecthub.pro', 'W@rung01', 'client'],
            ]
        );
    }

    private function orgUnit(string $name, int $parentId): OrganizationUnit
    {
        return OrganizationUnit::firstOrCreate(
            ['company_id' => $this->company->id, 'parent_id' => $parentId, 'name' => $name],
            [...OrganizationUnit::nextCodeForParent($parentId, $this->company->id), 'is_active' => true]
        );
    }

    /** @param array<string, User> $employees */
    private function seedHris(User $owner, array $employees): void
    {
        $companyId = $this->company->id;
        $leaveTypes = LeaveType::where('company_id', $companyId)->get()->keyBy('code');
        $year = (int) now()->year;

        // ── Leave balances (tahun berjalan) ──────────────────────────────────
        foreach ($employees as $emp) {
            foreach (['TAHUNAN', 'SAKIT'] as $code) {
                $type = $leaveTypes->get($code);
                if (! $type) continue;

                LeaveBalance::updateOrCreate(
                    ['user_id' => $emp->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['company_id' => $companyId, 'quota' => $type->default_quota, 'used' => 0, 'carried_over' => 0]
                );
            }
        }

        // ── Leave requests (sample: approved, pending, rejected) ─────────────
        $leaveSamples = [
            ['emp' => 'dev1',      'type' => 'TAHUNAN', 'start' => now()->subDays(10), 'end' => now()->subDays(8),  'status' => 'approved', 'reason' => 'Acara keluarga di luar kota'],
            ['emp' => 'dev2',      'type' => 'SAKIT',   'start' => now()->subDays(3),  'end' => now()->subDays(3),  'status' => 'approved', 'reason' => 'Demam, surat dokter terlampir'],
            ['emp' => 'marketing', 'type' => 'TAHUNAN', 'start' => now()->addDays(5),  'end' => now()->addDays(7),  'status' => 'pending',  'reason' => 'Cuti tahunan liburan'],
            ['emp' => 'hr',        'type' => 'IZIN',    'start' => now()->subDays(15), 'end' => now()->subDays(15), 'status' => 'rejected', 'reason' => 'Keperluan pribadi'],
        ];
        foreach ($leaveSamples as $s) {
            $emp = $employees[$s['emp']];
            $type = $leaveTypes->get($s['type']);
            if (! $type) continue;

            $totalDays = $s['start']->diffInDays($s['end']) + 1;

            LeaveRequest::updateOrCreate(
                ['user_id' => $emp->id, 'leave_type_id' => $type->id, 'start_date' => $s['start']->toDateString()],
                [
                    'company_id'       => $companyId,
                    'end_date'         => $s['end']->toDateString(),
                    'total_days'       => $totalDays,
                    'reason'           => $s['reason'],
                    'status'           => $s['status'],
                    'approved_by'      => $s['status'] === 'approved' ? $owner->id : null,
                    'approved_at'      => $s['status'] === 'approved' ? $s['start']->copy()->subDay() : null,
                    'rejection_reason' => $s['status'] === 'rejected' ? 'Beban kerja tim sedang tinggi periode tsb.' : null,
                ]
            );

            if ($s['status'] === 'approved') {
                $balance = LeaveBalance::where('user_id', $emp->id)->where('leave_type_id', $type->id)->where('year', $year)->first();
                if ($balance) {
                    $balance->used = $totalDays;
                    $balance->save();
                }
            }
        }

        // ── Attendance: 20 hari kerja terakhir ───────────────────────────────
        foreach ($employees as $key => $emp) {
            if (! $emp->shift_id) continue;

            $cursor = now()->copy()->subDays(40);
            $count = 0;
            while ($count < 20) {
                $cursor->addDay();
                if ($cursor->isWeekend() || $cursor->isFuture()) continue;
                $count++;

                $roll = $count % 10;
                if ($roll === 0) {
                    // absen tanpa keterangan
                    Attendance::updateOrCreate(
                        ['user_id' => $emp->id, 'date' => $cursor->toDateString()],
                        ['company_id' => $companyId, 'status' => 'alpha', 'notes' => null]
                    );
                    continue;
                }

                $late = $roll === 1; // sesekali telat
                $checkIn = $cursor->copy()->setTimeFromTimeString($emp->shift_id === $employees['dev2']->shift_id ? '13:00:00' : '08:00:00')
                    ->addMinutes($late ? 25 : rand(0, 5));
                $checkOut = $checkIn->copy()->addHours(9)->subMinutes(rand(0, 10));

                Attendance::updateOrCreate(
                    ['user_id' => $emp->id, 'date' => $cursor->toDateString()],
                    [
                        'company_id' => $companyId,
                        'check_in'   => $checkIn,
                        'check_out'  => $checkOut,
                        'status'     => 'hadir',
                        'notes'      => $late ? 'Datang terlambat' : null,
                    ]
                );
            }
        }

        // ── Overtime (sample) ─────────────────────────────────────────────────
        $overtimeSamples = [
            ['emp' => 'dev1', 'date' => now()->subDays(6), 'hours' => 2, 'desc' => 'Deploy production Website Redesign'],
            ['emp' => 'dev2', 'date' => now()->subDays(4), 'hours' => 3, 'desc' => 'Perbaikan bug kritikal Mobile App'],
        ];
        foreach ($overtimeSamples as $s) {
            $emp = $employees[$s['emp']];
            $upahSejam = round(($emp->salaries()->latest('effective_date')->first()->gaji_pokok ?? 8000000) / 173, 2);
            $amount = round($upahSejam * 1.5 * $s['hours'], 2);

            Overtime::updateOrCreate(
                ['user_id' => $emp->id, 'date' => $s['date']->toDateString()],
                [
                    'company_id'   => $companyId,
                    'day_type'     => 'weekday',
                    'start_time'   => '17:00:00',
                    'end_time'     => Carbon::createFromTimeString('17:00:00')->addHours($s['hours'])->format('H:i:s'),
                    'total_hours'  => $s['hours'],
                    'upah_sejam'   => $upahSejam,
                    'total_amount' => $amount,
                    'breakdown'    => [['jam_ke' => 1, 'multiplier' => 1.5, 'amount' => $amount]],
                    'description'  => $s['desc'],
                    'status'       => 'approved',
                    'approved_by'  => $owner->id,
                    'approved_at'  => $s['date'],
                ]
            );
        }

        // ── Kasbon, Reimbursement, Bonus (sample) ────────────────────────────
        Kasbon::updateOrCreate(
            ['user_id' => $employees['dev1']->id, 'tanggal' => now()->subDays(20)->toDateString()],
            [
                'company_id' => $companyId, 'jumlah' => 3000000, 'cicilan_per_bulan' => 1000000,
                'sisa' => 2000000, 'status' => 'berjalan', 'keterangan' => 'Kebutuhan mendesak keluarga',
                'created_by' => $owner->id,
            ]
        );

        Reimbursement::updateOrCreate(
            ['user_id' => $employees['marketing']->id, 'title' => 'Transport meeting klien'],
            [
                'company_id' => $companyId, 'category' => 'transport', 'description' => 'Taksi ke kantor klien untuk presentasi campaign',
                'expense_date' => now()->subDays(7), 'amount' => 250000, 'status' => 'approved',
                'approved_by' => $owner->id, 'approved_at' => now()->subDays(6),
            ]
        );

        Bonus::updateOrCreate(
            ['user_id' => $employees['manager']->id, 'year' => $year, 'month' => now()->month, 'type' => 'bonus'],
            ['company_id' => $companyId, 'amount' => 1500000, 'description' => 'Bonus pencapaian target Q3', 'created_by' => $owner->id]
        );

        // ── Payroll (2 bulan terakhir) ────────────────────────────────────────
        foreach ($employees as $emp) {
            $salary = EmployeeSalary::where('user_id', $emp->id)->latest('effective_date')->first();
            if (! $salary) continue;

            foreach ([1, 0] as $monthsAgo) {
                $period = now()->copy()->subMonths($monthsAgo);
                $bruto = $salary->gaji_pokok + $salary->tunjangan_transport + $salary->tunjangan_makan + $salary->tunjangan_jabatan;
                $bpjsKes = $salary->bpjs_kesehatan ? round($salary->gaji_pokok * 0.01, 2) : 0;
                $bpjsTk = $salary->bpjs_ketenagakerjaan ? round($salary->gaji_pokok * 0.02, 2) : 0;
                $pph21 = round($bruto * 0.02, 2);
                $totalPotongan = $bpjsKes + $bpjsTk + $pph21;

                Payroll::updateOrCreate(
                    ['user_id' => $emp->id, 'year' => $period->year, 'month' => $period->month],
                    [
                        'company_id'           => $companyId,
                        'gaji_pokok'           => $salary->gaji_pokok,
                        'tunjangan_transport'  => $salary->tunjangan_transport,
                        'tunjangan_makan'      => $salary->tunjangan_makan,
                        'tunjangan_jabatan'    => $salary->tunjangan_jabatan,
                        'hari_kerja'           => 22,
                        'hari_hadir'           => 21,
                        'hari_cuti'            => 1,
                        'hari_alpha'           => 0,
                        'penghasilan_bruto'    => $bruto,
                        'potongan_bpjs_kes'    => $bpjsKes,
                        'potongan_bpjs_tk'     => $bpjsTk,
                        'potongan_pph21'       => $pph21,
                        'total_potongan'       => $totalPotongan,
                        'gaji_bersih'          => $bruto - $totalPotongan,
                        'status'               => $monthsAgo === 1 ? 'paid' : 'finalized',
                        'paid_at'              => $monthsAgo === 1 ? $period->copy()->endOfMonth() : null,
                    ]
                );
            }
        }
    }

    /**
     * @param array<string, User> $employees
     */
    private function seedTaskManagement(User $owner, array $employees, User $client): void
    {
        $companyId = $this->company->id;
        $manager = $employees['manager'];
        $dev1 = $employees['dev1'];
        $dev2 = $employees['dev2'];

        $slaByPriority = SlaPolicy::whereNull('project_id')->get()->keyBy('priority');
        $columnTemplate = BoardColumnTemplate::default();

        $projectsData = [
            [
                'name'        => 'Company Website Revamp',
                'description' => 'Redesign & rebuild company profile website dengan tampilan modern dan SEO-friendly.',
                'status'      => 'active',
                'start'       => now()->subDays(25),
                'end'         => now()->addDays(35),
                'budget'      => 75000000,
                'progress'    => 45,
            ],
            [
                'name'        => 'Internal HR Mobile App',
                'description' => 'Aplikasi mobile untuk presensi, pengajuan cuti, dan slip gaji karyawan.',
                'status'      => 'active',
                'start'       => now()->subDays(10),
                'end'         => now()->addDays(60),
                'budget'      => 150000000,
                'progress'    => 20,
            ],
        ];

        foreach ($projectsData as $pd) {
            $project = Project::updateOrCreate(
                ['company_id' => $companyId, 'name' => $pd['name']],
                [
                    'description' => $pd['description'],
                    'client_id'   => $client->id,
                    'manager_id'  => $manager->id,
                    'status'      => $pd['status'],
                    'start_date'  => $pd['start'],
                    'end_date'    => $pd['end'],
                    'budget'      => $pd['budget'],
                    'progress'    => $pd['progress'],
                ]
            );

            if ($project->boardColumns()->count() === 0 && $columnTemplate) {
                $columnTemplate->applyTo($project);
            }
            $columns = $project->boardColumns()->get()->keyBy('slug');

            foreach ([$owner->id => 'admin', $manager->id => 'manager', $dev1->id => 'developer', $dev2->id => 'developer', $client->id => 'client'] as $userId => $role) {
                ProjectMember::updateOrCreate(['project_id' => $project->id, 'user_id' => $userId], ['role' => $role]);
            }

            // Milestones
            $m1 = Milestone::updateOrCreate(
                ['project_id' => $project->id, 'title' => 'Perencanaan & Desain'],
                ['description' => 'Riset kebutuhan, wireframe, dan desain UI final.', 'start_date' => $pd['start'], 'due_date' => $pd['start']->copy()->addDays(14), 'status' => 'completed', 'assigned_to' => $dev1->id]
            );
            $m2 = Milestone::updateOrCreate(
                ['project_id' => $project->id, 'title' => 'Development'],
                ['description' => 'Implementasi fitur inti sesuai desain yang disepakati.', 'start_date' => $pd['start']->copy()->addDays(15), 'due_date' => $pd['start']->copy()->addDays(45), 'status' => 'in_progress', 'assigned_to' => $dev2->id]
            );
            $m3 = Milestone::updateOrCreate(
                ['project_id' => $project->id, 'title' => 'Testing & Go Live'],
                ['description' => 'QA menyeluruh, UAT bersama klien, dan deployment production.', 'start_date' => $pd['end']->copy()->subDays(15), 'due_date' => $pd['end'], 'status' => 'pending', 'assigned_to' => $manager->id]
            );

            // Sprints
            $sprint1 = Sprint::updateOrCreate(
                ['project_id' => $project->id, 'name' => 'Sprint 1'],
                ['milestone_id' => $m1->id, 'goal' => 'Selesaikan riset & wireframe', 'start_date' => $pd['start'], 'end_date' => $pd['start']->copy()->addDays(14), 'status' => 'completed', 'created_by' => $manager->id]
            );
            $sprint2 = Sprint::updateOrCreate(
                ['project_id' => $project->id, 'name' => 'Sprint 2'],
                ['milestone_id' => $m2->id, 'goal' => 'Bangun fitur-fitur utama', 'start_date' => now()->subDays(7), 'end_date' => now()->addDays(7), 'status' => 'active', 'created_by' => $manager->id]
            );

            // Tasks
            $tasksData = [
                ['title' => 'Riset kebutuhan & kompetitor', 'milestone' => $m1, 'sprint' => $sprint1, 'status' => 'done', 'priority' => 'medium', 'assignee' => $dev1, 'due' => $pd['start']->copy()->addDays(5)],
                ['title' => 'Buat wireframe & mockup', 'milestone' => $m1, 'sprint' => $sprint1, 'status' => 'done', 'priority' => 'high', 'assignee' => $dev1, 'due' => $pd['start']->copy()->addDays(10)],
                ['title' => 'Review desain dengan klien', 'milestone' => $m1, 'sprint' => $sprint1, 'status' => 'done', 'priority' => 'medium', 'assignee' => $manager, 'due' => $pd['start']->copy()->addDays(14)],
                ['title' => 'Setup project & CI/CD', 'milestone' => $m2, 'sprint' => $sprint2, 'status' => 'done', 'priority' => 'high', 'assignee' => $dev2, 'due' => now()->subDays(5)],
                ['title' => 'Implementasi halaman utama', 'milestone' => $m2, 'sprint' => $sprint2, 'status' => 'in_progress', 'priority' => 'high', 'assignee' => $dev1, 'due' => now()->addDays(3)],
                ['title' => 'Integrasi API backend', 'milestone' => $m2, 'sprint' => $sprint2, 'status' => 'in_progress', 'priority' => 'urgent', 'assignee' => $dev2, 'due' => now()->addDays(5)],
                ['title' => 'Unit test modul autentikasi', 'milestone' => $m2, 'sprint' => $sprint2, 'status' => 'review', 'priority' => 'medium', 'assignee' => $dev2, 'due' => now()->addDays(2)],
                ['title' => 'Optimasi performa halaman', 'milestone' => $m2, 'sprint' => null, 'status' => 'todo', 'priority' => 'low', 'assignee' => $dev1, 'due' => now()->addDays(20)],
                ['title' => 'Dokumentasi teknis API', 'milestone' => null, 'sprint' => null, 'status' => 'todo', 'priority' => 'low', 'assignee' => $dev2, 'due' => now()->addDays(25)],
                ['title' => 'Persiapan UAT bersama klien', 'milestone' => $m3, 'sprint' => null, 'status' => 'todo', 'priority' => 'medium', 'assignee' => $manager, 'due' => $pd['end']->copy()->subDays(10)],
            ];

            $taskModels = [];
            foreach ($tasksData as $td) {
                $task = Task::updateOrCreate(
                    ['project_id' => $project->id, 'title' => $td['title']],
                    [
                        'milestone_id'     => $td['milestone']?->id,
                        'sprint_id'        => $td['sprint']?->id,
                        'description'      => null,
                        'assigned_to'      => $td['assignee']->id,
                        'created_by'       => $manager->id,
                        'status'           => $td['status'],
                        'board_column_id'  => $columns[$td['status']]->id ?? null,
                        'priority'         => $td['priority'],
                        'due_date'         => $td['due'],
                        'start_date'       => $pd['start'],
                        'estimated_hours'  => rand(4, 24),
                    ]
                );
                $taskModels[] = $task;
            }

            // Time logs untuk beberapa task yang sudah/sedang dikerjakan
            foreach (array_slice($taskModels, 0, 4) as $i => $task) {
                $assignee = $task->assigned_to;
                $start = now()->subDays(6 - $i)->setTime(9, 0);
                TimeLog::updateOrCreate(
                    ['task_id' => $task->id, 'user_id' => $assignee, 'started_at' => $start],
                    ['ended_at' => $start->copy()->addHours(3), 'minutes' => 180, 'notes' => 'Progress harian task ini.', 'is_running' => false]
                );
            }

            // Bug tickets
            $ticketsData = [
                ['title' => 'Tombol submit tidak berfungsi di Safari', 'type' => 'bug', 'priority' => 'critical', 'status' => 'open', 'desc' => 'Form tidak submit ketika dibuka di Safari versi terbaru.'],
                ['title' => 'Layout rusak di layar tablet', 'type' => 'bug', 'priority' => 'high', 'status' => 'in_progress', 'desc' => 'Sidebar overlap dengan konten utama di resolusi 768px.'],
                ['title' => 'Permintaan tambah filter pencarian', 'type' => 'enhancement', 'priority' => 'medium', 'status' => 'assigned', 'desc' => 'Klien minta filter tanggal pada halaman laporan.'],
                ['title' => 'Response API lambat saat load data besar', 'type' => 'performance', 'priority' => 'high', 'status' => 'resolved', 'desc' => 'Endpoint /reports butuh >5 detik dengan >1000 data.'],
            ];
            foreach ($ticketsData as $t) {
                $sla = $slaByPriority->get($t['priority']);
                BugTicket::updateOrCreate(
                    ['project_id' => $project->id, 'title' => $t['title']],
                    [
                        'milestone_id'  => null,
                        'reporter_id'   => $client->id,
                        'assignee_id'   => $dev1->id,
                        'description'   => $t['desc'],
                        'type'          => $t['type'],
                        'priority'      => $t['priority'],
                        'status'        => $t['status'],
                        'sla_policy_id' => $sla?->id,
                        'sla_due_at'    => $sla ? now()->addMinutes($sla->resolution_minutes) : null,
                        'resolved_at'   => $t['status'] === 'resolved' ? now()->subDay() : null,
                    ]
                );
            }

            // Customer requests
            $requestsData = [
                ['title' => 'Tambah opsi dark mode', 'desc' => 'Mohon tambahkan mode gelap untuk kenyamanan pengguna malam hari.', 'status' => 'waiting_approval'],
                ['title' => 'Export laporan ke PDF', 'desc' => 'Dibutuhkan fitur export laporan bulanan ke format PDF.', 'status' => 'approved'],
            ];
            foreach ($requestsData as $r) {
                CustomerRequest::updateOrCreate(
                    ['project_id' => $project->id, 'title' => $r['title']],
                    ['company_id' => $companyId, 'customer_id' => $client->id, 'description' => $r['desc'], 'type' => 'feature_request', 'priority' => 'medium', 'status' => $r['status']]
                );
            }

            // Risks
            Risk::updateOrCreate(
                ['project_id' => $project->id, 'title' => 'Keterlambatan approval desain dari klien'],
                ['description' => 'Klien cenderung lambat memberi feedback desain, berpotensi menggeser jadwal.', 'category' => 'schedule', 'probability' => 3, 'impact' => 3, 'status' => 'open', 'mitigation_plan' => 'Set deadline review maksimal 3 hari kerja & follow-up rutin.', 'owner' => $manager->name, 'created_by' => $manager->id]
            );

            // Budget entries
            BudgetEntry::updateOrCreate(
                ['project_id' => $project->id, 'description' => 'Pembayaran DP dari klien'],
                ['type' => 'income', 'category' => 'Pendapatan Proyek', 'amount' => $pd['budget'] * 0.3, 'entry_date' => $pd['start'], 'created_by' => $owner->id]
            );
            BudgetEntry::updateOrCreate(
                ['project_id' => $project->id, 'description' => 'Biaya lisensi tools & hosting'],
                ['type' => 'expense', 'category' => 'Software', 'amount' => 2500000, 'entry_date' => now()->subDays(10), 'created_by' => $owner->id]
            );

            // Invoice
            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => 'INV-' . strtoupper(str_replace(' ', '', substr($pd['name'], 0, 3))) . '-001'],
                [
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'client_id'  => $client->id,
                    'status'     => 'sent',
                    'issue_date' => now()->subDays(7),
                    'due_date'   => now()->addDays(23),
                    'tax'        => 11,
                    'notes'      => 'Invoice tahap pertama - ' . $pd['name'],
                ]
            );
            $item1 = InvoiceItem::updateOrCreate(
                ['invoice_id' => $invoice->id, 'description' => 'Design & Perencanaan'],
                ['quantity' => 1, 'unit_price' => $pd['budget'] * 0.2, 'total' => $pd['budget'] * 0.2]
            );
            $item2 = InvoiceItem::updateOrCreate(
                ['invoice_id' => $invoice->id, 'description' => 'Development (tahap 1)'],
                ['quantity' => 1, 'unit_price' => $pd['budget'] * 0.1, 'total' => $pd['budget'] * 0.1]
            );
            $subtotal = $item1->total + $item2->total;
            $invoice->update(['subtotal' => $subtotal, 'total' => $subtotal * 1.11]);

            // Knowledge base article
            KbArticle::updateOrCreate(
                ['project_id' => $project->id, 'title' => 'Panduan Onboarding Proyek ' . $pd['name']],
                ['author_id' => $manager->id, 'description' => 'Ringkasan alur kerja dan kontak penting untuk proyek ini.', 'body' => "# Onboarding\n\nBerisi ringkasan tools, alur approval, dan kontak PIC proyek {$pd['name']}.", 'tags' => ['onboarding', 'panduan'], 'version' => 1]
            );
        }

        // Campaign & Leads (dummy CRM, tetap di modul task management)
        $campaign = Campaign::updateOrCreate(
            ['company_id' => $companyId, 'name' => 'Kampanye Peluncuran Q4 2026'],
            [
                'description' => 'Kampanye digital untuk mempromosikan layanan baru perusahaan.',
                'channel'     => 'social_media',
                'budget'      => 10000000,
                'actual_spend'=> 4500000,
                'start_date'  => now()->subDays(15),
                'end_date'    => now()->addDays(30),
                'status'      => 'active',
                'created_by'  => $employees['marketing']->id,
                'owner_id'    => $employees['marketing']->id,
                'impressions' => 25000,
                'clicks'      => 1200,
                'reach'       => 18000,
                'leads_count' => 3,
                'goal_leads'  => 20,
            ]
        );

        $leadsData = [
            ['name' => 'CV Sinar Jaya', 'contact' => '0812xxxxxxx', 'email' => 'contact@sinarjaya.example', 'status' => 'prospect', 'source' => 'social_media', 'score' => 7, 'value' => 25000000],
            ['name' => 'Toko Maju Bersama', 'contact' => '0813xxxxxxx', 'email' => 'info@majubersama.example', 'status' => 'lead', 'source' => 'referral', 'score' => 4, 'value' => 8000000],
            ['name' => 'PT Berkah Digital', 'contact' => '0814xxxxxxx', 'email' => 'hello@berkahdigital.example', 'status' => 'client', 'source' => 'website', 'score' => 9, 'value' => 60000000],
        ];
        foreach ($leadsData as $l) {
            Lead::updateOrCreate(
                ['campaign_id' => $campaign->id, 'name' => $l['name']],
                [
                    'contact' => $l['contact'], 'email' => $l['email'], 'company' => $l['name'],
                    'source' => $l['source'], 'score' => $l['score'], 'value' => $l['value'],
                    'status' => $l['status'], 'assigned_to' => $employees['marketing']->id,
                    'converted_to_client_at' => $l['status'] === 'client' ? now()->subDays(2) : null,
                ]
            );
        }
    }
}
