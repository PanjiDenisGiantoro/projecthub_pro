<?php

namespace Database\Seeders;

use App\Models\BoardColumnTemplate;
use App\Models\Company;
use App\Models\Label;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\User;
use App\Support\SystemRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DummyDataSeeder extends Seeder
{
    /** @var array<string, \App\Models\Task> */
    private array $allTasks = [];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🌱 Seeding dummy data for Management Flovig...');

        // ── 1. Company ────────────────────────────────────────────────────
        $company = Company::updateOrCreate(
            ['code' => 'FLOVIG'],
            ['name' => 'PT Flovig Digital Indonesia', 'is_active' => true]
        );

        // ── 2. Roles ──────────────────────────────────────────────────────
        foreach (SystemRoles::ALL as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // ── 3. Members (10 akun) ──────────────────────────────────────────
        $password = 'Meja26#!';

        $memberData = [
            ['name' => 'Andi Prasetyo', 'email' => 'andi.prasetyo@flovig.test'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@flovig.test'],
            ['name' => 'Citra Dewi', 'email' => 'citra.dewi@flovig.test'],
            ['name' => 'Dimas Kurniawan', 'email' => 'dimas.kurniawan@flovig.test'],
            ['name' => 'Eka Putri', 'email' => 'eka.putri@flovig.test'],
            ['name' => 'Fajar Ramadhan', 'email' => 'fajar.ramadhan@flovig.test'],
            ['name' => 'Gita Sari', 'email' => 'gita.sari@flovig.test'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra.wijaya@flovig.test'],
            ['name' => 'Indah Permata', 'email' => 'indah.permata@flovig.test'],
            ['name' => 'Joko Susilo', 'email' => 'joko.susilo@flovig.test'],
        ];

        $members = [];
        foreach ($memberData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'is_active' => true,
                    'timezone' => 'Asia/Jakarta',
                    'email_verified_at' => now(),
                    'company_id' => $company->id,
                ]
            );
            $user->syncRoles(['member']);
            $members[] = $user;
        }

        [$andi, $budi, $citra, $dimas, $eka, $fajar, $gita, $hendra, $indah, $joko] = $members;

        $this->command->info('  ✓ 10 member accounts created');

        // ── 4. Clients (3 akun) ───────────────────────────────────────────
        $clientData = [
            ['name' => 'PT. Abadi Nan Jaya', 'email' => 'ratna.komala@client-flovig.test'],
            ['name' => 'PT. Cipta Karya Bangunan', 'email' => 'surya.atmaja@client-flovig.test'],
            ['name' => 'PT. Kencana Sari Dewi', 'email' => 'wulan.maharani@client-flovig.test'],
        ];

        $clients = [];
        foreach ($clientData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'is_active' => true,
                    'timezone' => 'Asia/Jakarta',
                    'email_verified_at' => now(),
                    'company_id' => $company->id,
                ]
            );
            $user->syncRoles(['client']);
            $clients[] = $user;
        }

        [$ratna, $surya, $wulan] = $clients;

        $this->command->info('  ✓ 3 client accounts created');

        // ── 5. Project ────────────────────────────────────────────────────
        $project = Project::updateOrCreate(
            ['name' => 'Management Flovig'],
            [
                'company_id' => $company->id,
                'description' => 'Platform manajemen proyek all-in-one untuk tim Flovig. Mencakup landing page, sistem autentikasi, dashboard interaktif, project & task management, account profile, serta reporting. Dibangun dengan fokus pada user experience, skalabilitas, dan kolaborasi tim real-time.',
                'client_id' => $ratna->id,
                'manager_id' => $andi->id,
                'status' => 'active',
                'start_date' => '2026-07-01',
                'end_date' => '2026-11-30',
                'budget' => 0,
                'progress' => 0,
            ]
        );

        $this->command->info('  ✓ Project "Management Flovig" created');

        // ── 6. Board Columns (Kanban) ─────────────────────────────────────
        if ($project->boardColumns()->count() === 0) {
            $tpl = BoardColumnTemplate::default();
            if ($tpl) {
                $tpl->applyTo($project);
            } else {
                foreach ([
                    ['name' => 'To Do', 'slug' => 'todo', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                    ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                    ['name' => 'Review', 'slug' => 'review', 'color' => 'purple', 'sort_order' => 2, 'is_done' => false],
                    ['name' => 'Done', 'slug' => 'done', 'color' => 'green', 'sort_order' => 3, 'is_done' => true],
                ] as $col) {
                    $project->boardColumns()->create($col);
                }
            }
        }

        $colTodo = $project->boardColumns()->where('slug', 'todo')->first();
        $colInProgress = $project->boardColumns()->where('slug', 'in_progress')->first();
        $colReview = $project->boardColumns()->where('slug', 'review')->first();
        $colDone = $project->boardColumns()->where('slug', 'done')->first();

        $colMap = [
            'todo' => $colTodo,
            'in_progress' => $colInProgress,
            'review' => $colReview,
            'done' => $colDone,
        ];

        // ── 7. Project Members ────────────────────────────────────────────
        ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $andi->id],
            ['role' => 'manager']
        );

        foreach (array_slice($members, 1) as $member) {
            ProjectMember::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $member->id],
                ['role' => 'developer']
            );
        }

        ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $ratna->id],
            ['role' => 'client']
        );

        $this->command->info('  ✓ 11 project members added');

        // ── 8. Labels ─────────────────────────────────────────────────────
        $labelData = [
            'Frontend' => 'blue',
            'Backend' => 'green',
            'UI/UX' => 'purple',
            'Bug Fix' => 'red',
            'Enhancement' => 'teal',
            'Documentation' => 'gray',
            'API' => 'orange',
            'Testing/QA' => 'yellow',
            'Security' => 'indigo',
            'DevOps' => 'pink',
        ];

        $labels = [];
        foreach ($labelData as $name => $color) {
            $labels[$name] = Label::updateOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['color' => $color]
            );
        }

        $lid = fn(array $names) => collect($names)->map(fn($n) => $labels[$n]->id)->toArray();

        $this->command->info('  ✓ 10 labels created');

        // ── 9. Milestones ─────────────────────────────────────────────────
        $ms1 = Milestone::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Foundation & Landing Page'],
            [
                'description' => 'Setup arsitektur project, implementasi landing page publik dengan hero section, fitur overview, pricing, FAQ, dan contact form. Termasuk setup CI/CD, konfigurasi environment, dan design system.',
                'start_date' => '2026-07-01',
                'due_date' => '2026-08-10',
                'assigned_to' => $andi->id,
                'priority' => 'high',
                'release_target' => 'Phase 3 Release (Q3 2026)',
                'status' => 'completed',
            ]
        );

        $ms2 = Milestone::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Authentication & Account Management'],
            [
                'description' => 'Implementasi sistem autentikasi lengkap (login, register, forgot password, email verification, OAuth Google), manajemen profil user, dan role-based access control.',
                'start_date' => '2026-08-11',
                'due_date' => '2026-09-14',
                'assigned_to' => $budi->id,
                'priority' => 'urgent',
                'release_target' => 'Phase 3 Release (Q3 2026)',
                'status' => 'in_progress',
            ]
        );

        $ms3 = Milestone::updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Project & Task Management Core'],
            [
                'description' => 'Fitur inti project management: CRUD project, milestone, sprint, task board (kanban), task detail, assignment, labeling, checklist, attachment, time tracking, dan dashboard analitik project.',
                'start_date' => '2026-09-15',
                'due_date' => '2026-11-30',
                'assigned_to' => $dimas->id,
                'priority' => 'high',
                'release_target' => 'Phase 4 Release (Q4 2026)',
                'status' => 'pending',
            ]
        );

        $this->command->info('  ✓ 3 milestones created');

        // ── 10. Sprints ───────────────────────────────────────────────────
        $sprintDefs = [
            // Milestone 1
            ['milestone' => $ms1, 'name' => 'Project Setup & Design System', 'goal' => 'Setup boilerplate, design token, CI/CD pipeline, dan development environment agar siap untuk fase development.', 'start' => '2026-07-01', 'end' => '2026-07-14', 'status' => 'completed', 'priority' => 'high', 'lead' => $fajar],
            ['milestone' => $ms1, 'name' => 'Landing Page Core', 'goal' => 'Build hero section, feature showcase, pricing table, testimonial, dan responsive layout untuk landing page.', 'start' => '2026-07-15', 'end' => '2026-07-28', 'status' => 'completed', 'priority' => 'normal', 'lead' => $citra],
            ['milestone' => $ms1, 'name' => 'Landing Page Polish & SEO', 'goal' => 'Optimasi performa, SEO, animasi, dark mode toggle, dan integrasi contact form.', 'start' => '2026-07-29', 'end' => '2026-08-10', 'status' => 'completed', 'priority' => 'normal', 'lead' => $gita],
            // Milestone 2
            ['milestone' => $ms2, 'name' => 'Auth Core (Login/Register)', 'goal' => 'Implementasi halaman login, register, email verification, dan forgot password dengan validasi lengkap.', 'start' => '2026-08-11', 'end' => '2026-08-24', 'status' => 'active', 'priority' => 'urgent', 'lead' => $budi],
            ['milestone' => $ms2, 'name' => 'OAuth & Account Profile', 'goal' => 'Google OAuth integration, halaman profil user, avatar upload, dan notification preferences.', 'start' => '2026-08-25', 'end' => '2026-09-07', 'status' => 'active', 'priority' => 'high', 'lead' => $eka],
            ['milestone' => $ms2, 'name' => 'RBAC & User Management', 'goal' => 'Implementasi role-based access control dengan Spatie Permission, user invitation, dan admin user management.', 'start' => '2026-09-08', 'end' => '2026-09-14', 'status' => 'active', 'priority' => 'normal', 'lead' => $hendra],
            // Milestone 3
            ['milestone' => $ms3, 'name' => 'Project CRUD & Dashboard', 'goal' => 'CRUD project, project dashboard dengan statistik, member management, dan label system.', 'start' => '2026-09-15', 'end' => '2026-09-28', 'status' => 'active', 'priority' => 'high', 'lead' => $dimas],
            ['milestone' => $ms3, 'name' => 'Milestone, Sprint & Kanban Board', 'goal' => 'Milestone management, sprint planning, kanban board, dan drag & drop functionality.', 'start' => '2026-09-29', 'end' => '2026-10-12', 'status' => 'active', 'priority' => 'high', 'lead' => $andi],
            ['milestone' => $ms3, 'name' => 'Task Detail & Collaboration', 'goal' => 'Task detail panel, checklist (DoD), attachments, comments, time log, dan reporting module.', 'start' => '2026-10-13', 'end' => '2026-11-30', 'status' => 'planned', 'priority' => 'normal', 'lead' => $indah],
        ];

        $sprints = [];
        foreach ($sprintDefs as $sd) {
            $sprints[] = Sprint::updateOrCreate(
                ['project_id' => $project->id, 'name' => $sd['name']],
                [
                    'milestone_id' => $sd['milestone']->id,
                    'goal' => $sd['goal'],
                    'start_date' => $sd['start'],
                    'end_date' => $sd['end'],
                    'status' => $sd['status'],
                    'priority' => $sd['priority'],
                    'assigned_to' => $sd['lead']->id,
                    'created_by' => $andi->id,
                ]
            );
        }

        [$sp1, $sp2, $sp3, $sp4, $sp5, $sp6, $sp7, $sp8, $sp9] = $sprints;

        $this->command->info('  ✓ 9 sprints created');

        // ── 11. Tasks ─────────────────────────────────────────────────────

        // Helper closure to create one task and store it by title
        $mkTask = function (array $d) use ($project, $andi, $colMap) {
            $boardCol = $colMap[$d['status']] ?? $colMap['todo'];

            $task = Task::updateOrCreate(
                ['project_id' => $project->id, 'title' => $d['title']],
                [
                    'milestone_id' => $d['milestone_id'] ?? null,
                    'sprint_id' => $d['sprint_id'] ?? null,
                    'description' => $d['description'] ?? null,
                    'assigned_to' => $d['assigned_to'],
                    'created_by' => $d['created_by'] ?? $andi->id,
                    'status' => $d['status'],
                    'board_column_id' => $boardCol?->id,
                    'priority' => $d['priority'],
                    'start_date' => $d['start_date'] ?? null,
                    'due_date' => $d['due_date'] ?? null,
                    'estimated_hours' => $d['estimated_hours'] ?? null,
                    'story_points' => $d['story_points'] ?? null,
                    'sort_order' => $d['sort_order'] ?? 0,
                    'completion_notes' => $d['completion_notes'] ?? null,
                ]
            );

            if (!empty($d['labels'])) {
                $task->labels()->syncWithoutDetaching($d['labels']);
            }

            $memberIds = array_values(array_filter(array_unique(array_merge(
                [$d['assigned_to']],
                $d['extra_members'] ?? []
            ))));
            if (!empty($memberIds)) {
                $task->members()->syncWithoutDetaching($memberIds);
            }

            $this->allTasks[$d['title']] = $task;

            return $task;
        };

        // ─── SP-01: Project Setup & Design System (MS-01) ────────────────

        $mkTask([
            'title' => 'Setup Laravel & Vite boilerplate',
            'description' => "## Objective\nSetup fresh Laravel project dengan Vite sebagai build tool.\n\n## Requirements\n- Laravel 11.x with latest patches\n- Vite untuk asset bundling\n- PostCSS & Autoprefixer configured\n- Hot Module Replacement (HMR) working\n\n## Completed\nBoilerplate berhasil di-setup dengan semua konfigurasi. HMR berjalan lancar di development.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp1->id,
            'status' => 'done',
            'priority' => 'urgent',
            'assigned_to' => $fajar->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-07-01',
            'due_date' => '2026-07-03',
            'labels' => $lid(['Backend', 'DevOps']),
            'sort_order' => 1,
            'completion_notes' => 'Boilerplate ready. Vite HMR confirmed working.',
        ]);

        $mkTask([
            'title' => 'Konfigurasi Tailwind CSS & Design Token',
            'description' => "Setup Tailwind CSS dengan custom design tokens untuk konsistensi visual.\n\n- Color palette (primary, secondary, accent, neutral)\n- Typography scale (Inter font family)\n- Spacing system (4px base)\n- Border radius tokens\n- Shadow system (sm, md, lg, xl)",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp1->id,
            'status' => 'done',
            'priority' => 'high',
            'assigned_to' => $citra->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-02',
            'due_date' => '2026-07-04',
            'labels' => $lid(['Frontend', 'UI/UX']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Setup CI/CD pipeline (GitHub Actions)',
            'description' => "Konfigurasi GitHub Actions untuk automated testing dan deployment.\n\n### Pipeline Steps\n1. Run PHPUnit tests on PR\n2. Run ESLint/Prettier checks\n3. Build assets\n4. Deploy to staging on merge to develop\n5. Deploy to production on merge to main",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp1->id,
            'status' => 'done',
            'priority' => 'high',
            'assigned_to' => $fajar->id,
            'extra_members' => [$fajar->id, $hendra->id],
            'story_points' => 8,
            'estimated_hours' => 12,
            'start_date' => '2026-07-03',
            'due_date' => '2026-07-07',
            'labels' => $lid(['DevOps']),
            'sort_order' => 3,
            'completion_notes' => 'CI/CD pipeline active. Auto-test on PR, auto-deploy to staging.',
        ]);

        $mkTask([
            'title' => 'Konfigurasi database & migration awal',
            'description' => "Setup database MySQL, buat migration untuk tabel users, companies, dan konfigurasi dasar. Pastikan foreign key constraints benar dan index optimal.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp1->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $budi->id,
            'story_points' => 3,
            'estimated_hours' => 4,
            'start_date' => '2026-07-04',
            'due_date' => '2026-07-06',
            'labels' => $lid(['Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Buat component library (Button, Input, Modal)',
            'description' => "Buat reusable Blade components:\n- Button (primary, secondary, danger, ghost, loading state)\n- Input (text, email, password, textarea, with error state)\n- Modal (confirmation, form, full-screen)\n- Alert, Badge, Avatar, Dropdown",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp1->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-07-05',
            'due_date' => '2026-07-14',
            'labels' => $lid(['Frontend', 'UI/UX']),
            'sort_order' => 5,
        ]);

        // ─── SP-02: Landing Page Core (MS-01) ───────────────────────────

        $mkTask([
            'title' => 'Design wireframe landing page',
            'description' => "Buat wireframe lengkap untuk landing page di Figma:\n- Hero section layout\n- Feature cards grid (3 kolom)\n- Pricing comparison table (3 tier)\n- Testimonial carousel\n- Footer dengan navigation links\n\nWireframe sudah di-approve oleh stakeholder.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'high',
            'assigned_to' => $citra->id,
            'extra_members' => [$citra->id, $gita->id],
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-07-15',
            'due_date' => '2026-07-17',
            'labels' => $lid(['UI/UX']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Implementasi hero section dengan animasi',
            'description' => "Build hero section sesuai wireframe:\n- Gradient background (animated)\n- Animated text reveal (typewriter effect)\n- CTA button dengan hover glow effect\n- Responsive untuk mobile/tablet/desktop\n- Illustration integration",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'high',
            'assigned_to' => $citra->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-07-17',
            'due_date' => '2026-07-20',
            'labels' => $lid(['Frontend']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Build feature showcase section',
            'description' => "Section yang menampilkan fitur-fitur utama platform dengan icon, title, dan description. Grid layout 3 kolom di desktop, 1 kolom di mobile. Hover animation per card.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $eka->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-18',
            'due_date' => '2026-07-22',
            'labels' => $lid(['Frontend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Implementasi pricing table component',
            'description' => "Pricing comparison table dengan 3 tier: Starter, Professional, Enterprise. Toggle monthly/annual billing. Highlight recommended plan. Smooth transition antar toggle.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-20',
            'due_date' => '2026-07-24',
            'labels' => $lid(['Frontend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Build testimonial carousel',
            'description' => "Carousel/slider untuk testimonial klien. Auto-play (5 detik interval), manual navigation (arrow + dots), responsive, pause on hover.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'low',
            'assigned_to' => $indah->id,
            'story_points' => 2,
            'estimated_hours' => 4,
            'start_date' => '2026-07-22',
            'due_date' => '2026-07-25',
            'labels' => $lid(['Frontend', 'UI/UX']),
            'sort_order' => 5,
        ]);

        $mkTask([
            'title' => 'Responsive layout testing semua breakpoint',
            'description' => "Testing menyeluruh di semua breakpoint: 320px, 375px, 768px, 1024px, 1280px, 1536px. Screenshot comparison. Fix semua layout issues yang ditemukan.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp2->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $dimas->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-25',
            'due_date' => '2026-07-28',
            'labels' => $lid(['Testing/QA', 'Frontend']),
            'sort_order' => 6,
        ]);

        // ─── SP-03: Landing Page Polish & SEO (MS-01) ────────────────────

        $mkTask([
            'title' => 'SEO meta tags & Open Graph setup',
            'description' => "Setup SEO lengkap:\n- Title tags per halaman\n- Meta descriptions\n- Open Graph tags (Facebook/LinkedIn)\n- Twitter Card tags\n- Canonical URLs\n- Sitemap.xml generation\n- robots.txt configuration",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp3->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $fajar->id,
            'story_points' => 2,
            'estimated_hours' => 4,
            'start_date' => '2026-07-29',
            'due_date' => '2026-07-31',
            'labels' => $lid(['Frontend', 'Documentation']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Implementasi dark mode toggle',
            'description' => "Toggle dark/light mode:\n- System preference detection (prefers-color-scheme)\n- Manual toggle di navbar\n- Persistent setting di localStorage\n- Smooth CSS transition antar mode\n- Semua section sudah support dark mode",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp3->id,
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-30',
            'due_date' => '2026-08-02',
            'labels' => $lid(['Frontend', 'Enhancement']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Contact form dengan validasi & email',
            'description' => "Build contact form:\n- Client-side & server-side validation\n- CSRF protection\n- Rate limiting (max 5 submissions/hour)\n- Email notification ke admin (queued)\n- Success/error feedback UI\n- Honeypot anti-spam field",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp3->id,
            'status' => 'review',
            'priority' => 'high',
            'assigned_to' => $budi->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-05',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Performance audit & Lighthouse optimization',
            'description' => "Lighthouse audit & optimization target:\n- Performance >90\n- Accessibility >95\n- Best Practices >95\n- SEO >95\n\nOptimizations: WebP images, lazy loading, code splitting, critical CSS, Brotli compression.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp3->id,
            'status' => 'review',
            'priority' => 'medium',
            'assigned_to' => $hendra->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-08-04',
            'due_date' => '2026-08-07',
            'labels' => $lid(['DevOps', 'Testing/QA']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'FAQ accordion section',
            'description' => "Build FAQ section dengan accordion component. Smooth expand/collapse animation. Support 8-10 FAQ items. Only one open at a time.",
            'milestone_id' => $ms1->id,
            'sprint_id' => $sp3->id,
            'status' => 'todo',
            'priority' => 'low',
            'assigned_to' => $indah->id,
            'story_points' => 2,
            'estimated_hours' => 3,
            'start_date' => '2026-08-05',
            'due_date' => '2026-08-08',
            'labels' => $lid(['Frontend']),
            'sort_order' => 5,
        ]);

        // ─── SP-04: Auth Core — Login/Register (MS-02) ──────────────────

        $mkTask([
            'title' => 'Halaman login dengan validasi',
            'description' => "## Acceptance Criteria\n- Form login (email + password)\n- Client-side validation (required, email format)\n- Server-side validation (credentials check)\n- Error messages yang informatif\n- Redirect ke intended URL setelah login\n- Link ke register & forgot password\n- Mobile responsive",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp4->id,
            'status' => 'review',
            'priority' => 'urgent',
            'assigned_to' => $budi->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-08-11',
            'due_date' => '2026-08-14',
            'labels' => $lid(['Frontend', 'Backend', 'Security']),
            'sort_order' => 1,
            'completion_notes' => 'Login flow complete with validation, CSRF, and rate limiting.',
        ]);

        $mkTask([
            'title' => 'Halaman register & email verification',
            'description' => "## Requirements\n- Registration form (name, email, password, confirm password)\n- Password strength indicator\n- Terms of service checkbox\n- Email verification flow (queued email)\n- Resend verification link\n- Auto-redirect after verification",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp4->id,
            'status' => 'review',
            'priority' => 'urgent',
            'assigned_to' => $budi->id,
            'extra_members' => [$budi->id, $eka->id],
            'story_points' => 8,
            'estimated_hours' => 14,
            'start_date' => '2026-08-13',
            'due_date' => '2026-08-18',
            'labels' => $lid(['Backend', 'Security']),
            'sort_order' => 2,
            'completion_notes' => 'Register + email verification working. Using queued mail.',
        ]);

        $mkTask([
            'title' => 'Forgot password & reset flow',
            'description' => "Flow forgot password:\n1. User klik 'Lupa Password'\n2. Input email → kirim reset link (queued)\n3. User klik link di email → form reset password\n4. Set password baru → redirect ke login\n\nToken expired setelah 60 menit.",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp4->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $eka->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-08-16',
            'due_date' => '2026-08-19',
            'labels' => $lid(['Backend', 'Security']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Remember me & session management',
            'description' => "Implementasi remember me functionality:\n- Remember me checkbox di login\n- Session lifetime configuration\n- Concurrent session handling\n- Logout from all devices option",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp4->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $hendra->id,
            'story_points' => 3,
            'estimated_hours' => 4,
            'start_date' => '2026-08-18',
            'due_date' => '2026-08-21',
            'labels' => $lid(['Backend', 'Security']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Rate limiting & brute force protection',
            'description' => "Proteksi login:\n- Rate limiting login endpoint (5 attempts/minute)\n- Account lockout setelah 10 failed attempts (15 min cooldown)\n- IP-based rate limiting\n- Logging failed attempts ke activity log",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp4->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $fajar->id,
            'story_points' => 3,
            'estimated_hours' => 5,
            'start_date' => '2026-08-20',
            'due_date' => '2026-08-24',
            'labels' => $lid(['Backend', 'Security']),
            'sort_order' => 5,
        ]);

        // ─── SP-05: OAuth & Account Profile (MS-02) ─────────────────────

        $mkTask([
            'title' => 'Google OAuth integration',
            'description' => "## In Progress\nIntegrasi Google OAuth via Laravel Socialite.\n\n### Progress\n- [x] Install Socialite package\n- [x] Configure Google API credentials\n- [x] Redirect to Google flow\n- [ ] Callback handler & user creation/linking\n- [ ] Link existing account scenario\n- [ ] Error handling & edge cases",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp5->id,
            'status' => 'review',
            'priority' => 'high',
            'assigned_to' => $eka->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-08-25',
            'due_date' => '2026-09-01',
            'labels' => $lid(['Backend', 'API']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Halaman profil user (view & edit)',
            'description' => "Halaman profil user:\n- View profil (nama, email, avatar, timezone, tanggal join)\n- Edit profil (nama, email) — inline edit\n- Change password (old + new + confirm)\n- Delete account (with confirmation modal)\n- Responsive layout",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp5->id,
            'status' => 'review',
            'priority' => 'high',
            'assigned_to' => $citra->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-08-26',
            'due_date' => '2026-09-03',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Avatar upload dengan crop & resize',
            'description' => "Upload avatar user:\n- File upload (jpg, png, webp, max 2MB)\n- Client-side image crop (square aspect ratio)\n- Server-side resize (128×128, 256×256)\n- Storage di local disk / S3-compatible\n- Default avatar via UI Avatars API",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp5->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-08-28',
            'due_date' => '2026-09-03',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Timezone & notification preferences',
            'description' => "Setting user:\n- Timezone selector (semua timezone IANA)\n- Email notification preferences (on/off per category)\n- Push notification opt-in/out\n- Save preferences via AJAX (no page reload)",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp5->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $indah->id,
            'story_points' => 3,
            'estimated_hours' => 5,
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-05',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Activity log halaman profil',
            'description' => "Tampilkan activity log user di halaman profil:\n- Login history (device, IP, timestamp)\n- Recent actions (created project, updated task, etc.)\n- Pagination (15 per page)\n- Filter by date range",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp5->id,
            'status' => 'in_progress',
            'priority' => 'low',
            'assigned_to' => $dimas->id,
            'story_points' => 2,
            'estimated_hours' => 4,
            'start_date' => '2026-09-03',
            'due_date' => '2026-09-07',
            'labels' => $lid(['Backend', 'Enhancement']),
            'sort_order' => 5,
        ]);

        // ─── SP-06: RBAC & User Management (MS-02) ──────────────────────

        $mkTask([
            'title' => 'Implementasi Spatie Permission roles',
            'description' => "Setup role & permission system:\n- Roles: admin, member, client\n- Default permissions per role\n- Permission check middleware\n- Blade directives (@role, @can)\n- API policy gates",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp6->id,
            'status' => 'review',
            'priority' => 'high',
            'assigned_to' => $hendra->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-09-08',
            'due_date' => '2026-09-10',
            'labels' => $lid(['Backend', 'Security']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'User invitation via email',
            'description' => "Invite user baru ke platform:\n- Admin bisa invite via email\n- Generate invitation link (expired 7 days)\n- User klik link → halaman set password\n- Auto-assign role & company\n- Resend invitation option",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp6->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $budi->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-09-09',
            'due_date' => '2026-09-12',
            'labels' => $lid(['Backend', 'API']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Admin user management CRUD',
            'description' => "Halaman admin manage users:\n- List users (search, filter by role/status, pagination)\n- Create user (with role assignment)\n- Edit user (profile, role, status)\n- Activate/deactivate user\n- Bulk actions (activate, deactivate)",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp6->id,
            'status' => 'review',
            'priority' => 'medium',
            'assigned_to' => $andi->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-09-10',
            'due_date' => '2026-09-13',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Permission management UI',
            'description' => "UI manage permissions:\n- Matrix view (roles × permissions)\n- Toggle permission per role\n- Custom role creation\n- Permission grouping by module\n- Bulk permission set/unset",
            'milestone_id' => $ms2->id,
            'sprint_id' => $sp6->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $dimas->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-09-11',
            'due_date' => '2026-09-14',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 4,
        ]);

        // ─── SP-07: Project CRUD & Dashboard (MS-03) ─────────────────────

        $mkTask([
            'title' => 'CRUD project (create, edit, archive, delete)',
            'description' => "Fitur CRUD project:\n- Create project (nama, deskripsi, client, manager, tanggal, budget)\n- Edit project settings\n- Archive/restore project\n- Soft delete project\n- Validation & authorization checks",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp7->id,
            'status' => 'review',
            'priority' => 'high',
            'assigned_to' => $dimas->id,
            'story_points' => 8,
            'estimated_hours' => 14,
            'start_date' => '2026-09-15',
            'due_date' => '2026-09-20',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Project dashboard dengan statistik',
            'description' => "Dashboard per project:\n- Overview stats cards (task count, completion %, overdue tasks)\n- Progress chart (line/area chart)\n- Team workload distribution (bar chart)\n- Recent activity feed\n- Upcoming deadlines widget",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp7->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $andi->id,
            'story_points' => 8,
            'estimated_hours' => 14,
            'start_date' => '2026-09-17',
            'due_date' => '2026-09-23',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Project member management',
            'description' => "Manage anggota project:\n- Add member (search user by name/email, assign project role)\n- Remove member (with confirmation)\n- Change member role\n- Set max hours per day\n- Member list dengan avatar & role badge",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp7->id,
            'status' => 'review',
            'priority' => 'medium',
            'assigned_to' => $fajar->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-09-20',
            'due_date' => '2026-09-24',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Label management per project',
            'description' => "CRUD label per project:\n- Create label (name, color picker 10 warna)\n- Edit label\n- Delete label (confirmation, show affected tasks count)\n- Label preview inline\n- Quick assign label ke task",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp7->id,
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 3,
            'estimated_hours' => 5,
            'start_date' => '2026-09-22',
            'due_date' => '2026-09-25',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Project file & folder management',
            'description' => "File management per project:\n- Upload files (drag & drop, multi-file)\n- Create/rename/delete folders\n- Move files between folders\n- Preview (image, PDF)\n- Download single file / bulk ZIP",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp7->id,
            'status' => 'todo',
            'priority' => 'low',
            'assigned_to' => $citra->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-09-24',
            'due_date' => '2026-09-28',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 5,
        ]);

        // ─── SP-08: Milestone, Sprint & Kanban (MS-03) ──────────────────

        $mkTask([
            'title' => 'CRUD Milestone dengan progress tracking',
            'description' => "Milestone management:\n- CRUD (title, description, dates, assignee, priority, release target)\n- Auto progress berdasarkan task completion\n- Timeline/Gantt visualization\n- Status transitions (planning → in_progress → completed)\n- Overdue warning indicator",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp8->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $andi->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-09-29',
            'due_date' => '2026-10-03',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Sprint planning & management',
            'description' => "Sprint management:\n- Create/edit sprint (name, goal, dates, lead, priority)\n- Sprint backlog view (tasks list)\n- Move tasks between sprints (drag or dropdown)\n- Sprint velocity tracking (story points)\n- Sprint summary report on completion",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp8->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $dimas->id,
            'story_points' => 8,
            'estimated_hours' => 12,
            'start_date' => '2026-09-30',
            'due_date' => '2026-10-06',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Kanban board dengan drag & drop',
            'description' => "Kanban board:\n- Kolom-kolom board (To Do, In Progress, Review, Done)\n- Drag & drop task antar kolom (SortableJS)\n- Persist status change via AJAX\n- Visual feedback saat drag (shadow, highlight)\n- Touch support untuk mobile\n- Column WIP limits (optional)",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp8->id,
            'status' => 'in_progress',
            'priority' => 'urgent',
            'assigned_to' => $citra->id,
            'extra_members' => [$citra->id, $eka->id],
            'story_points' => 13,
            'estimated_hours' => 20,
            'start_date' => '2026-10-01',
            'due_date' => '2026-10-09',
            'labels' => $lid(['Frontend', 'UI/UX']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Board column customization',
            'description' => "Kustomisasi kolom board per project:\n- Add/remove/rename kolom\n- Change kolom color\n- Reorder kolom via drag\n- Set kolom sebagai 'done' (marks tasks complete)\n- Apply template to project",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp8->id,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $hendra->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-10-05',
            'due_date' => '2026-10-09',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Sprint velocity & burndown chart',
            'description' => "Sprint analytics:\n- Burndown chart (ideal line vs actual line)\n- Velocity chart (story points completed per sprint, bar chart)\n- Scope change indicator\n- Export data to CSV\n- Chart library: Chart.js atau ApexCharts",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp8->id,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $fajar->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-10-06',
            'due_date' => '2026-10-12',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 5,
        ]);

        // ─── SP-09: Task Detail & Collaboration (MS-03) ─────────────────

        $mkTask([
            'title' => 'Task detail panel (sidebar/modal)',
            'description' => "Task detail panel (slide-over sidebar):\n- Show semua field task (title, description, priority, dates, assignee)\n- Inline edit (click to edit title, description, priority, dates)\n- Assignee picker (avatar + dropdown)\n- Label picker (multi-select)\n- Status change (dropdown)\n- Related tasks / dependencies",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp9->id,
            'status' => 'todo',
            'priority' => 'high',
            'assigned_to' => $indah->id,
            'story_points' => 8,
            'estimated_hours' => 14,
            'start_date' => '2026-10-13',
            'due_date' => '2026-10-23',
            'labels' => $lid(['Frontend', 'UI/UX']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Task checklist (Definition of Done)',
            'description' => "Checklist system per task:\n- Create checklist group (e.g. \"Definition of Done\")\n- Add/edit/remove items\n- Toggle done/undone (checkbox)\n- Reorder items (drag & drop)\n- Progress bar indicator\n- Multiple checklist groups per task",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp9->id,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $gita->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-10-20',
            'due_date' => '2026-10-30',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Task attachment (file & link)',
            'description' => "Attachment system:\n- Upload file (drag & drop, max 10MB per file)\n- Add link attachment (URL + display name)\n- Preview image attachments (lightbox)\n- Download file\n- Delete attachment\n- File type icons (PDF, image, doc, etc.)",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp9->id,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $budi->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-10-25',
            'due_date' => '2026-11-05',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Task comments & mentions',
            'description' => "Comment system:\n- Add comment (rich text editor)\n- Edit/delete own comment\n- @mention user (autocomplete)\n- Comment attachments\n- Real-time update (polling or WebSocket)\n- Email notification on mention",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp9->id,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $eka->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-12',
            'labels' => $lid(['Frontend', 'Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Time logging per task',
            'description' => "Time tracking:\n- Log time manual (hours, minutes, description)\n- Start/stop timer (running timer)\n- Time log history per task\n- Summary per user per sprint\n- Export timesheet (CSV)",
            'milestone_id' => $ms3->id,
            'sprint_id' => $sp9->id,
            'status' => 'todo',
            'priority' => 'low',
            'assigned_to' => $hendra->id,
            'story_points' => 5,
            'estimated_hours' => 8,
            'start_date' => '2026-11-10',
            'due_date' => '2026-11-20',
            'labels' => $lid(['Backend', 'Frontend']),
            'sort_order' => 5,
        ]);

        // ─── Standalone Tasks (milestone only, no sprint) ────────────────

        $mkTask([
            'title' => 'Buat dokumentasi technical architecture',
            'description' => "Dokumentasi arsitektur teknis project:\n- System architecture diagram (C4 model)\n- Database schema (ERD)\n- API endpoint list\n- Technology stack rationale\n- Deployment architecture (staging + production)\n- Security considerations & compliance",
            'milestone_id' => $ms1->id,
            'sprint_id' => null,
            'status' => 'todo',
            'priority' => 'low',
            'assigned_to' => $andi->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-07-05',
            'due_date' => '2026-07-15',
            'labels' => $lid(['Documentation']),
            'sort_order' => 1,
        ]);

        $mkTask([
            'title' => 'Setup error monitoring (Sentry)',
            'description' => "Setup Sentry untuk error monitoring:\n- Install Sentry Laravel SDK\n- Configure DSN & environment tags\n- Custom context (user, company, project)\n- Source maps upload untuk JS errors\n- Alert rules untuk critical/fatal errors",
            'milestone_id' => $ms1->id,
            'sprint_id' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $fajar->id,
            'story_points' => 3,
            'estimated_hours' => 4,
            'start_date' => '2026-07-10',
            'due_date' => '2026-07-18',
            'labels' => $lid(['DevOps']),
            'sort_order' => 2,
        ]);

        $mkTask([
            'title' => 'Buat user acceptance test plan',
            'description' => "Buat UAT plan untuk fitur autentikasi:\n- Test scenarios per fitur (login, register, reset, OAuth)\n- Test data preparation\n- Acceptance criteria matrix\n- Bug report template\n- UAT schedule & sign-off form",
            'milestone_id' => $ms2->id,
            'sprint_id' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $indah->id,
            'story_points' => 3,
            'estimated_hours' => 6,
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-10',
            'labels' => $lid(['Testing/QA', 'Documentation']),
            'sort_order' => 3,
        ]);

        $mkTask([
            'title' => 'Integration testing API auth',
            'description' => "Buat integration test untuk semua API auth:\n- Login endpoint (success, invalid, locked)\n- Register endpoint (validation, duplicate)\n- Forgot password (valid email, invalid, rate limit)\n- Email verification flow\n- OAuth callback (new user, existing user)\n\nGunakan PHPUnit + Laravel HTTP tests.",
            'milestone_id' => $ms2->id,
            'sprint_id' => null,
            'status' => 'todo',
            'priority' => 'high',
            'assigned_to' => $joko->id,
            'story_points' => 5,
            'estimated_hours' => 10,
            'start_date' => '2026-09-05',
            'due_date' => '2026-09-14',
            'labels' => $lid(['Testing/QA', 'API', 'Backend']),
            'sort_order' => 4,
        ]);

        $mkTask([
            'title' => 'Buat project reporting module',
            'description' => "Module reporting project:\n- Project summary report (PDF export)\n- Sprint completion report\n- Team performance report (tasks per member)\n- Budget utilization report\n- Time tracking summary report\n- Custom date range filter\n- Export to CSV/Excel",
            'milestone_id' => $ms3->id,
            'sprint_id' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assigned_to' => $joko->id,
            'story_points' => 8,
            'estimated_hours' => 16,
            'start_date' => '2026-10-15',
            'due_date' => '2026-11-30',
            'labels' => $lid(['Backend', 'Frontend', 'Enhancement']),
            'sort_order' => 5,
        ]);

        $this->command->info('  ✓ ' . count($this->allTasks) . ' tasks created');

        // ── 12. Checklists (Definition of Done) ──────────────────────────
        $this->seedChecklists($members);

        // ── 13. Attachments (link type) ──────────────────────────────────
        $this->seedAttachments($members);

        // ── 14. Comments ──────────────────────────────────────────────────
        $this->seedComments($members);

        // ── Summary ───────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('✅  Dummy data seeded successfully!');
        $this->command->info('');
        $this->command->table(
            ['Type', 'Email', 'Password', 'Role'],
            [
                ['Member (×10)', 'andi.prasetyo@flovig.test ...', 'Meja26#!', 'member'],
                ['Client (×3)', 'ratna.komala@client-flovig.test ...', 'Meja26#!', 'client'],
            ]
        );
        $this->command->info('');
        $this->command->info('  Project    : Management Flovig (active, Jul–Nov 2026)');
        $this->command->info('  Milestones : 3 (completed → in_progress → planning)');
        $this->command->info('  Sprints    : 9 (completed/active/planned)');
        $this->command->info('  Tasks      : ' . count($this->allTasks));
        $this->command->info('  Labels     : 10');
        $this->command->info('');
    }

    // ─── Checklists ──────────────────────────────────────────────────────

    private function seedChecklists(array $members): void
    {
        [$andi, $budi, $citra, $dimas, $eka, $fajar, $gita, $hendra, $indah, $joko] = $members;

        $checklists = [
            'Setup Laravel & Vite boilerplate' => [
                'Definition of Done' => [
                    ['title' => 'Laravel installed & configured', 'is_done' => true, 'by' => $fajar],
                    ['title' => 'Vite build working (dev & prod)', 'is_done' => true, 'by' => $fajar],
                    ['title' => 'Database connection verified', 'is_done' => true, 'by' => $fajar],
                    ['title' => '.env.example updated', 'is_done' => true, 'by' => $fajar],
                ],
            ],
            'Setup CI/CD pipeline (GitHub Actions)' => [
                'Definition of Done' => [
                    ['title' => 'GitHub Actions workflow created', 'is_done' => true, 'by' => $fajar],
                    ['title' => 'Auto-test on PR (PHPUnit)', 'is_done' => true, 'by' => $hendra],
                    ['title' => 'Auto-deploy to staging on merge', 'is_done' => true, 'by' => $fajar],
                    ['title' => 'Production deploy pipeline', 'is_done' => false, 'by' => null],
                ],
            ],
            'Halaman login dengan validasi' => [
                'Definition of Done' => [
                    ['title' => 'Login form with validation', 'is_done' => true, 'by' => $budi],
                    ['title' => 'Error messages displayed properly', 'is_done' => true, 'by' => $budi],
                    ['title' => 'Redirect to dashboard on success', 'is_done' => true, 'by' => $budi],
                    ['title' => 'Remember me functionality', 'is_done' => true, 'by' => $budi],
                    ['title' => 'Mobile responsive verified', 'is_done' => true, 'by' => $citra],
                ],
            ],
            'Kanban board dengan drag & drop' => [
                'Definition of Done' => [
                    ['title' => 'Drag & drop tasks between columns', 'is_done' => false, 'by' => null],
                    ['title' => 'Persist column changes via API', 'is_done' => false, 'by' => null],
                    ['title' => 'Visual feedback during drag', 'is_done' => false, 'by' => null],
                    ['title' => 'Touch support for mobile', 'is_done' => false, 'by' => null],
                    ['title' => 'Column WIP limits', 'is_done' => false, 'by' => null],
                ],
                'Technical Notes' => [
                    ['title' => 'Use SortableJS library', 'is_done' => false, 'by' => null],
                    ['title' => 'Debounce API calls (300ms)', 'is_done' => false, 'by' => null],
                    ['title' => 'Optimistic UI update', 'is_done' => false, 'by' => null],
                ],
            ],
        ];

        foreach ($checklists as $taskTitle => $groups) {
            $task = $this->allTasks[$taskTitle] ?? null;
            if (!$task) {
                continue;
            }

            $groupOrder = 0;
            foreach ($groups as $groupTitle => $items) {
                $checklist = TaskChecklist::updateOrCreate(
                    ['task_id' => $task->id, 'title' => $groupTitle],
                    ['sort_order' => $groupOrder++]
                );

                $itemOrder = 0;
                foreach ($items as $item) {
                    TaskChecklistItem::updateOrCreate(
                        ['checklist_id' => $checklist->id, 'title' => $item['title']],
                        [
                            'is_done' => $item['is_done'],
                            'sort_order' => $itemOrder++,
                            'completed_at' => $item['is_done'] ? now()->subDays(rand(5, 30)) : null,
                            'completed_by' => $item['is_done'] ? $item['by']?->id : null,
                        ]
                    );
                }
            }
        }

        $this->command->info('  ✓ Checklists seeded');
    }

    // ─── Attachments ─────────────────────────────────────────────────────

    private function seedAttachments(array $members): void
    {
        [$andi, $budi, $citra, $dimas, $eka, $fajar, $gita, $hendra, $indah, $joko] = $members;

        $attachments = [
            'Setup Laravel & Vite boilerplate' => [
                ['type' => 'link', 'file_name' => 'GitHub Repository', 'url' => 'https://github.com/flovig/management-flovig', 'created_by' => $fajar],
            ],
            'Setup CI/CD pipeline (GitHub Actions)' => [
                ['type' => 'link', 'file_name' => 'GitHub Actions Config', 'url' => 'https://github.com/flovig/management-flovig/actions', 'created_by' => $fajar],
            ],
            'Design wireframe landing page' => [
                ['type' => 'link', 'file_name' => 'Figma Wireframe', 'url' => 'https://figma.com/file/flovig-wireframe-landing', 'created_by' => $citra],
            ],
            'Google OAuth integration' => [
                ['type' => 'link', 'file_name' => 'Google OAuth2 Docs', 'url' => 'https://developers.google.com/identity/protocols/oauth2', 'created_by' => $eka],
                ['type' => 'link', 'file_name' => 'Socialite Documentation', 'url' => 'https://laravel.com/docs/socialite', 'created_by' => $eka],
            ],
            'Kanban board dengan drag & drop' => [
                ['type' => 'link', 'file_name' => 'SortableJS Library', 'url' => 'https://sortablejs.github.io/Sortable/', 'created_by' => $citra],
            ],
            'Halaman profil user (view & edit)' => [
                ['type' => 'link', 'file_name' => 'Figma Profile Design', 'url' => 'https://figma.com/file/flovig-profile-design', 'created_by' => $citra],
            ],
        ];

        foreach ($attachments as $taskTitle => $items) {
            $task = $this->allTasks[$taskTitle] ?? null;
            if (!$task) {
                continue;
            }

            foreach ($items as $att) {
                TaskAttachment::updateOrCreate(
                    ['task_id' => $task->id, 'url' => $att['url']],
                    [
                        'type' => $att['type'],
                        'file_name' => $att['file_name'],
                        'created_by' => $att['created_by']->id,
                    ]
                );
            }
        }

        $this->command->info('  ✓ Attachments seeded');
    }

    // ─── Comments ────────────────────────────────────────────────────────

    private function seedComments(array $members): void
    {
        [$andi, $budi, $citra, $dimas, $eka, $fajar, $gita, $hendra, $indah, $joko] = $members;

        $comments = [
            'Setup CI/CD pipeline (GitHub Actions)' => [
                ['user' => $hendra, 'body' => 'Pipeline sudah jalan lancar. Test coverage saat ini 78%. Target kita 85% sebelum release.'],
                ['user' => $andi, 'body' => 'Nice work! Untuk production deploy, kita pakai manual trigger dulu ya, jangan auto-deploy. Nanti setelah QA sign-off baru release.'],
            ],
            'Halaman login dengan validasi' => [
                ['user' => $citra, 'body' => 'Login page sudah responsive. Tested di iPhone 12, Samsung S21, dan iPad. Semua OK.'],
                ['user' => $budi, 'body' => 'Terima kasih reviewnya @Citra. Sudah ditambahkan juga loading state di button saat proses login.'],
            ],
            'Google OAuth integration' => [
                ['user' => $eka, 'body' => 'Redirect ke Google sudah work. Sekarang lagi handle callback — perlu decide: kalau email sudah ada di system tapi belum link Google, auto-link atau minta konfirmasi?'],
                ['user' => $andi, 'body' => 'Minta konfirmasi dulu ke user. Tampilkan modal "Akun dengan email ini sudah ada. Mau link dengan Google?" untuk security.'],
            ],
            'Avatar upload dengan crop & resize' => [
                ['user' => $gita, 'body' => 'Crop component sudah jadi pakai Cropper.js. Sekarang tinggal handle server-side resize dan storage.'],
            ],
            'Kanban board dengan drag & drop' => [
                ['user' => $dimas, 'body' => 'Untuk kanban board, saya suggest pakai SortableJS karena lebih lightweight dari react-beautiful-dnd dan support touch devices out of the box.'],
                ['user' => $citra, 'body' => 'Setuju pakai SortableJS. Saya sudah buat prototype kecil, performance-nya smooth bahkan dengan 100+ cards.'],
            ],
            'Performance audit & Lighthouse optimization' => [
                ['user' => $hendra, 'body' => 'Lighthouse scores setelah optimasi:\n- Performance: 94\n- Accessibility: 98\n- Best Practices: 100\n- SEO: 97\n\nSemua di atas target! 🎉'],
            ],
        ];

        foreach ($comments as $taskTitle => $entries) {
            $task = $this->allTasks[$taskTitle] ?? null;
            if (!$task) {
                continue;
            }

            foreach ($entries as $i => $entry) {
                TaskComment::updateOrCreate(
                    ['task_id' => $task->id, 'user_id' => $entry['user']->id, 'body' => $entry['body']],
                    []
                );
            }
        }

        $this->command->info('  ✓ Comments seeded');
    }
}
