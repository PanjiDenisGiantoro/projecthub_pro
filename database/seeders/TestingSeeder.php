<?php

namespace Database\Seeders;

use App\Models\BudgetEntry;
use App\Models\BugTicket;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CustomerRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Milestone;
use App\Models\OrganizationUnit;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\StructuralLevel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Struktur Organisasi ────────────────────────────────────────────
        $company = Company::updateOrCreate(
            ['code' => 'PTTEST'],
            ['name' => 'PT Testing Indonesia', 'is_active' => true]
        );

        $rootUnit = OrganizationUnit::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => null, 'name' => 'Kantor Pusat'],
            [...OrganizationUnit::nextCodeForParent(null, $company->id), 'is_active' => true]
        );

        $divisionUnit = OrganizationUnit::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => $rootUnit->id, 'name' => 'IT & Development'],
            [...OrganizationUnit::nextCodeForParent($rootUnit->id, $company->id), 'is_active' => true]
        );

        $department = OrganizationUnit::firstOrCreate(
            ['company_id' => $company->id, 'parent_id' => $divisionUnit->id, 'name' => 'Engineering'],
            [...OrganizationUnit::nextCodeForParent($divisionUnit->id, $company->id), 'is_active' => true]
        );

        $levelStaff   = StructuralLevel::firstOrCreate(['name' => 'Staff'],   ['sort_order' => 3]);
        $levelManager = StructuralLevel::firstOrCreate(['name' => 'Manager'], ['sort_order' => 2]);
        $levelAdmin   = StructuralLevel::firstOrCreate(['name' => 'Admin'],   ['sort_order' => 1]);

        // ── 2. Roles ──────────────────────────────────────────────────────────
        foreach (['admin', 'manager', 'developer', 'marketing', 'customer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // ── 3. Users ──────────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@projecthub.pro'],
            [
                'name'                 => 'Admin ProjectHub',
                'password'             => 'password',
                'is_active'            => true,
                'timezone'             => 'Asia/Jakarta',
                'organization_unit_id' => $department->id,
                'structural_level_id'  => $levelAdmin->id,
            ]
        );
        $admin->syncRoles(['admin']);

        $manager = User::updateOrCreate(
            ['email' => 'manager@projecthub.pro'],
            [
                'name'                => 'Manager One',
                'password'            => 'password',
                'is_active'           => true,
                'timezone'            => 'Asia/Jakarta',
                'organization_unit_id' => $department->id,
                'structural_level_id' => $levelManager->id,
            ]
        );
        $manager->syncRoles(['manager']);

        $dev = User::updateOrCreate(
            ['email' => 'dev@projecthub.pro'],
            [
                'name'                => 'Developer One',
                'password'            => 'password',
                'is_active'           => true,
                'timezone'            => 'Asia/Jakarta',
                'organization_unit_id' => $department->id,
                'structural_level_id' => $levelStaff->id,
            ]
        );
        $dev->syncRoles(['developer']);

        $customer = User::updateOrCreate(
            ['email' => 'client@projecthub.pro'],
            [
                'name'                => 'Client One',
                'password'            => 'password',
                'is_active'           => true,
                'timezone'            => 'Asia/Jakarta',
                'organization_unit_id' => $department->id,
                'structural_level_id' => $levelStaff->id,
            ]
        );
        $customer->syncRoles(['customer']);

        // ── 4. Projects ───────────────────────────────────────────────────────
        $project1 = Project::updateOrCreate(
            ['name' => 'Website Redesign'],
            [
                'description' => 'Redesign website utama perusahaan dengan tampilan modern.',
                'client_id'   => $customer->id,
                'manager_id'  => $manager->id,
                'status'      => 'active',
                'start_date'  => now()->subDays(30),
                'end_date'    => now()->addDays(60),
                'budget'      => 50000000,
                'progress'    => 40,
            ]
        );

        $project2 = Project::updateOrCreate(
            ['name' => 'Mobile App Development'],
            [
                'description' => 'Pengembangan aplikasi mobile untuk iOS dan Android.',
                'client_id'   => $customer->id,
                'manager_id'  => $manager->id,
                'status'      => 'active',
                'start_date'  => now()->subDays(15),
                'end_date'    => now()->addDays(90),
                'budget'      => 120000000,
                'progress'    => 20,
            ]
        );

        // ── 5. Project Members ────────────────────────────────────────────────
        $members = [
            [$project1->id, $admin->id,    'admin'],
            [$project1->id, $manager->id,  'manager'],
            [$project1->id, $dev->id,      'developer'],
            [$project1->id, $customer->id, 'client'],
            [$project2->id, $admin->id,    'admin'],
            [$project2->id, $manager->id,  'manager'],
            [$project2->id, $dev->id,      'developer'],
            [$project2->id, $customer->id, 'client'],
        ];

        foreach ($members as [$projectId, $userId, $role]) {
            ProjectMember::updateOrCreate(
                ['project_id' => $projectId, 'user_id' => $userId],
                ['role' => $role]
            );
        }

        // ── 6. Milestones ─────────────────────────────────────────────────────
        $m1 = Milestone::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'Design Mockup'],
            ['due_date' => now()->addDays(10), 'status' => 'completed', 'description' => 'Selesaikan desain mockup halaman utama.']
        );

        $m2 = Milestone::updateOrCreate(
            ['project_id' => $project1->id, 'name' => 'Frontend Development'],
            ['due_date' => now()->addDays(30), 'status' => 'in_progress', 'description' => 'Implementasi tampilan frontend.']
        );

        $m3 = Milestone::updateOrCreate(
            ['project_id' => $project2->id, 'name' => 'API Development'],
            ['due_date' => now()->addDays(40), 'status' => 'pending', 'description' => 'Buat REST API untuk mobile app.']
        );

        // ── 7. Tasks ──────────────────────────────────────────────────────────
        $tasks = [
            // Project 1
            ['project_id' => $project1->id, 'milestone_id' => $m1->id, 'title' => 'Buat wireframe homepage',        'status' => 'done',        'priority' => 'high',   'assigned_to' => $dev->id,     'due_date' => now()->subDays(5)],
            ['project_id' => $project1->id, 'milestone_id' => $m1->id, 'title' => 'Review desain dengan klien',     'status' => 'done',        'priority' => 'medium', 'assigned_to' => $manager->id, 'due_date' => now()->subDays(2)],
            ['project_id' => $project1->id, 'milestone_id' => $m2->id, 'title' => 'Setup project Next.js',          'status' => 'in_progress', 'priority' => 'high',   'assigned_to' => $dev->id,     'due_date' => now()->addDays(5)],
            ['project_id' => $project1->id, 'milestone_id' => $m2->id, 'title' => 'Implementasi halaman landing',   'status' => 'in_progress', 'priority' => 'high',   'assigned_to' => $dev->id,     'due_date' => now()->addDays(10)],
            ['project_id' => $project1->id, 'milestone_id' => $m2->id, 'title' => 'Integrasi CMS',                  'status' => 'todo',        'priority' => 'medium', 'assigned_to' => $dev->id,     'due_date' => now()->addDays(20)],
            ['project_id' => $project1->id, 'milestone_id' => null,    'title' => 'Testing & QA Website',           'status' => 'todo',        'priority' => 'low',    'assigned_to' => $manager->id, 'due_date' => now()->addDays(55)],
            // Project 2
            ['project_id' => $project2->id, 'milestone_id' => $m3->id, 'title' => 'Setup Laravel backend',          'status' => 'in_progress', 'priority' => 'critical', 'assigned_to' => $dev->id,   'due_date' => now()->addDays(7)],
            ['project_id' => $project2->id, 'milestone_id' => $m3->id, 'title' => 'Buat endpoint authentication',   'status' => 'todo',        'priority' => 'high',     'assigned_to' => $dev->id,   'due_date' => now()->addDays(14)],
            ['project_id' => $project2->id, 'milestone_id' => null,    'title' => 'Design UI Mobile (Figma)',        'status' => 'done',        'priority' => 'medium',   'assigned_to' => $dev->id,   'due_date' => now()->subDays(3)],
            ['project_id' => $project2->id, 'milestone_id' => null,    'title' => 'Setup CI/CD Pipeline',           'status' => 'todo',        'priority' => 'low',      'assigned_to' => $admin->id, 'due_date' => now()->addDays(30)],
        ];

        foreach ($tasks as $taskData) {
            Task::updateOrCreate(
                ['project_id' => $taskData['project_id'], 'title' => $taskData['title']],
                array_merge($taskData, ['created_by' => $manager->id, 'estimated_hours' => rand(4, 16)])
            );
        }

        // ── 8. Bug Tickets ────────────────────────────────────────────────────
        $tickets = [
            ['project_id' => $project1->id, 'title' => 'Navbar tidak tampil di mobile',   'priority' => 'high',     'status' => 'open',        'reported_by' => $customer->id, 'assigned_to' => $dev->id,     'description' => 'Navbar hilang saat dibuka di iPhone Safari.'],
            ['project_id' => $project1->id, 'title' => 'Tombol CTA tidak berfungsi',       'priority' => 'critical', 'status' => 'in_progress', 'reported_by' => $manager->id,  'assigned_to' => $dev->id,     'description' => 'Tombol "Hubungi Kami" tidak merespons klik.'],
            ['project_id' => $project1->id, 'title' => 'Loading halaman terlalu lambat',   'priority' => 'medium',   'status' => 'resolved',    'reported_by' => $customer->id, 'assigned_to' => $dev->id,     'description' => 'Halaman utama butuh >5 detik untuk load.'],
            ['project_id' => $project2->id, 'title' => 'Login gagal di Android',           'priority' => 'critical', 'status' => 'open',        'reported_by' => $customer->id, 'assigned_to' => $dev->id,     'description' => 'User tidak bisa login di Android 12 ke atas.'],
            ['project_id' => $project2->id, 'title' => 'Push notification tidak masuk',    'priority' => 'high',     'status' => 'open',        'reported_by' => $manager->id,  'assigned_to' => $dev->id,     'description' => 'Notifikasi tidak muncul di background.'],
        ];

        foreach ($tickets as $ticketData) {
            BugTicket::updateOrCreate(
                ['project_id' => $ticketData['project_id'], 'title' => $ticketData['title']],
                $ticketData
            );
        }

        // ── 9. Customer Requests ──────────────────────────────────────────────
        $requests = [
            ['title' => 'Tambah fitur dark mode',           'description' => 'Mohon tambahkan opsi dark mode pada website.', 'status' => 'pending',  'requested_by' => $customer->id, 'project_id' => $project1->id],
            ['title' => 'Integrasi WhatsApp Chat',          'description' => 'Tambahkan tombol chat WhatsApp di semua halaman.', 'status' => 'approved', 'requested_by' => $customer->id, 'project_id' => $project1->id],
            ['title' => 'Export laporan ke Excel',          'description' => 'Dibutuhkan fitur export data ke format Excel.', 'status' => 'pending',  'requested_by' => $customer->id, 'project_id' => $project2->id],
        ];

        foreach ($requests as $req) {
            CustomerRequest::updateOrCreate(
                ['title' => $req['title'], 'project_id' => $req['project_id']],
                $req
            );
        }

        // ── 10. Invoice ───────────────────────────────────────────────────────
        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-TEST-001'],
            [
                'project_id'     => $project1->id,
                'client_id'      => $customer->id,
                'created_by'     => $admin->id,
                'status'         => 'sent',
                'issue_date'     => now()->subDays(7),
                'due_date'       => now()->addDays(23),
                'notes'          => 'Invoice tahap pertama Website Redesign.',
                'tax_percent'    => 11,
            ]
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Design & Wireframe'],
            ['quantity' => 1, 'unit_price' => 10000000, 'unit' => 'paket']
        );

        InvoiceItem::updateOrCreate(
            ['invoice_id' => $invoice->id, 'description' => 'Frontend Development (40 jam)'],
            ['quantity' => 40, 'unit_price' => 350000, 'unit' => 'jam']
        );

        // ── 11. Budget Entries ────────────────────────────────────────────────
        BudgetEntry::updateOrCreate(
            ['project_id' => $project1->id, 'description' => 'Biaya Lisensi Figma'],
            ['type' => 'expense', 'amount' => 500000, 'date' => now()->subDays(20), 'recorded_by' => $admin->id]
        );

        BudgetEntry::updateOrCreate(
            ['project_id' => $project1->id, 'description' => 'Pembayaran DP Klien'],
            ['type' => 'income', 'amount' => 25000000, 'date' => now()->subDays(28), 'recorded_by' => $admin->id]
        );

        // ── 12. Campaign ──────────────────────────────────────────────────────
        Campaign::updateOrCreate(
            ['name' => 'Kampanye Peluncuran Website'],
            [
                'project_id'  => $project1->id,
                'created_by'  => $manager->id,
                'status'      => 'active',
                'type'        => 'digital',
                'start_date'  => now()->subDays(10),
                'end_date'    => now()->addDays(20),
                'budget'      => 5000000,
                'description' => 'Kampanye digital untuk peluncuran website baru.',
            ]
        );

        $this->command->info('');
        $this->command->info('✅  Testing data seeded successfully!');
        $this->command->info('');
        $this->command->table(
            ['Role', 'Email', 'Password', 'Akses'],
            [
                ['admin',     'admin@projecthub.pro',   'password', '/dashboard → semua menu'],
                ['manager',   'manager@projecthub.pro', 'password', '/dashboard → project, task, ticket, invoice'],
                ['developer', 'dev@projecthub.pro',     'password', '/dashboard → task, ticket, sprint, KB'],
                ['customer',  'client@projecthub.pro',  'password', '/dashboard → ticket, request, invoice'],
            ]
        );
        $this->command->info('');
        $this->command->info('  Projects  : Website Redesign, Mobile App Development');
        $this->command->info('  Tasks     : 10 tasks (berbagai status)');
        $this->command->info('  Tickets   : 5 bug tickets');
        $this->command->info('  Requests  : 3 customer requests');
        $this->command->info('  Invoice   : INV-TEST-001 (status: sent)');
        $this->command->info('');
    }
}
