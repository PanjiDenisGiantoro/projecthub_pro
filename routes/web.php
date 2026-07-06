<?php

use App\Http\Controllers\Web\AnalyticsWebController;
use App\Http\Controllers\Web\ChatWebController;
use App\Http\Controllers\Web\ApprovalWebController;
use App\Http\Controllers\Web\PermissionWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\RegisterWebController;
use App\Http\Controllers\Web\VerificationController;
use App\Http\Controllers\Web\BranchWebController;
use App\Http\Controllers\Web\BudgetWebController;
use App\Http\Controllers\Web\CalendarWebController;
use App\Http\Controllers\Web\CampaignWebController;
use App\Http\Controllers\Web\ClientPortalWebController;
use App\Http\Controllers\Web\ClientWebController;
use App\Http\Controllers\Web\CompanyWebController;
use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\DepartmentWebController;
use App\Http\Controllers\Web\DivisionWebController;
use App\Http\Controllers\Web\ExportWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\KbArticleWebController;
use App\Http\Controllers\Web\MasterDataWebController;
use App\Http\Controllers\Web\MilestoneWebController;
use App\Http\Controllers\Web\ProjectFileWebController;
use App\Http\Controllers\Web\ProjectTemplateWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\RecurringTaskWebController;
use App\Http\Controllers\Web\RequestWebController;
use App\Http\Controllers\Web\RiskWebController;
use App\Http\Controllers\Web\SearchWebController;
use App\Http\Controllers\Web\SprintWebController;
use App\Http\Controllers\Web\TaskWebController;
use App\Http\Controllers\Web\TicketWebController;
use App\Http\Controllers\Web\AjaxController;
use App\Http\Controllers\Web\RoleWebController;
use App\Http\Controllers\Web\StructuralLevelWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\Hris\AbsensiController;
use App\Http\Controllers\Web\Hris\LeaveController;
use App\Http\Controllers\Web\Hris\OvertimeController;
use App\Http\Controllers\Web\Hris\ReimbursementController;
use App\Http\Controllers\Web\Hris\EmployeeSalaryController;
use App\Http\Controllers\Web\Hris\PayrollController;
use App\Http\Controllers\Web\Hris\Master\HrisMasterController;
use App\Http\Controllers\Web\Hris\Master\LeaveTypeController;
use App\Http\Controllers\Web\Hris\Master\OvertimeRuleController;
use App\Http\Controllers\Web\Hris\Master\TaxPtkpController;
use App\Http\Controllers\Web\Hris\Master\TaxBracketController;
use App\Http\Controllers\DeployWebhookController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

// ─── Deploy Webhook (tanpa auth & CSRF, dilindungi token) ────────────────────
Route::prefix('deploy')->name('deploy.')->group(function () {
    Route::post('/webhook', [DeployWebhookController::class, 'trigger'])->name('webhook');
    Route::get('/log',      [DeployWebhookController::class, 'log'])->name('log');
    Route::get('/status',   [DeployWebhookController::class, 'status'])->name('status');
});

// ─── Public ───────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('landing'))->name('home');
Route::get('/daftar', [RegisterWebController::class, 'show'])->name('register');
Route::post('/daftar', [RegisterWebController::class, 'store'])->name('register.post');

// ─── Super Admin ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'check.active', 'verified', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/companies', [SuperAdminController::class, 'companies'])->name('companies');
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    Route::patch('/companies/{company}/toggle', [SuperAdminController::class, 'toggleCompany'])->name('companies.toggle');
    Route::get('/registered-users', [SuperAdminController::class, 'registeredUsers'])->name('registered-users');
    Route::post('/registered-users', [SuperAdminController::class, 'storeRegisteredUser'])->name('registered-users.store');
    Route::patch('/registered-users/{user}/lifetime', [SuperAdminController::class, 'updateLifetime'])->name('registered-users.lifetime');
});

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');
Route::get('/akun-kadaluarsa', [AuthWebController::class, 'expired'])->name('account.expired');
Route::get('/login/google', [AuthWebController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/login/google/callback', [AuthWebController::class, 'handleGoogleCallback'])->name('login.google.callback');

// ─── Verifikasi Email ──────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{token}', [VerificationController::class, 'verify'])
        ->middleware(['throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'check.active', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardWebController::class, 'index'])->name('dashboard');

    // Package switcher
    Route::post('/switch-package', function (\Illuminate\Http\Request $request) {
        $pkg = $request->input('package');
        $valid = ['task_management', 'hris'];
        $allowed = auth()->user()->is_super_admin ? $valid : auth()->user()->activePackages();
        if (is_string($pkg) && in_array($pkg, $allowed)) {
            $request->session()->put('active_package', $pkg);
        }
        return redirect()->route('dashboard');
    })->name('switch.package');

    // Profile
    Route::get('/profile', [ProfileWebController::class, 'index'])->name('profile');
    Route::put('/profile/avatar', [ProfileWebController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileWebController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::put('/profile/password', [ProfileWebController::class, 'updatePassword'])->name('profile.password');

    // Projects
    Route::resource('projects', ProjectWebController::class);
    Route::post('/projects/{project}/members', [ProjectWebController::class, 'addMember'])->name('projects.members.add');
    Route::delete('/projects/{project}/members/{user}', [ProjectWebController::class, 'removeMember'])->name('projects.members.remove');

    // Milestones (within project)
    Route::post('/projects/{project}/milestones', [MilestoneWebController::class, 'store'])->name('milestones.store');
    Route::put('/projects/{project}/milestones/{milestone}', [MilestoneWebController::class, 'update'])->name('milestones.update');
    Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneWebController::class, 'destroy'])->name('milestones.destroy');

    // Tasks
    Route::get('/projects/{project}/tasks', [TaskWebController::class, 'index'])->name('tasks.index');
    Route::post('/projects/{project}/tasks', [TaskWebController::class, 'store'])->name('tasks.store');
    Route::get('/projects/{project}/tasks/{task}', [TaskWebController::class, 'show'])->name('tasks.show');
    Route::put('/projects/{project}/tasks/{task}', [TaskWebController::class, 'update'])->name('tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [TaskWebController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/time-logs', [TaskWebController::class, 'storeTimeLog'])->name('tasks.timelog.store');
    Route::patch('/projects/{project}/tasks/{task}/move', [TaskWebController::class, 'moveStatus'])->name('tasks.move');

    // Bug Tickets
    Route::get('/tickets', [TicketWebController::class, 'allTickets'])->name('tickets.all');
    Route::get('/projects/{project}/tickets', [TicketWebController::class, 'index'])->name('tickets.index');
    Route::get('/projects/{project}/tickets/create', [TicketWebController::class, 'create'])->name('tickets.create');
    Route::post('/projects/{project}/tickets', [TicketWebController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketWebController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}/assign', [TicketWebController::class, 'assign'])->name('tickets.assign');
    Route::put('/tickets/{ticket}/status', [TicketWebController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/comments', [TicketWebController::class, 'addComment'])->name('tickets.comment');
    Route::put('/tickets/{ticket}/reopen', [TicketWebController::class, 'reopen'])->name('tickets.reopen');

    // Approvals
    Route::get('/approvals', [ApprovalWebController::class, 'index'])->name('approvals.index');
    Route::put('/approvals/{approval}/approve', [ApprovalWebController::class, 'approve'])->name('approvals.approve');
    Route::put('/approvals/{approval}/reject', [ApprovalWebController::class, 'reject'])->name('approvals.reject');
    Route::delete('/approvals/{approval}', [ApprovalWebController::class, 'cancel'])->name('approvals.cancel');

    // Role Management (admin only)
    Route::resource('roles', RoleWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Permission Management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/permissions', [PermissionWebController::class, 'index'])->name('permissions.index');
        Route::put('/permissions/{role}', [PermissionWebController::class, 'update'])->name('permissions.update');
        Route::get('/permissions/{role}/reset', [PermissionWebController::class, 'resetRole'])->name('permissions.reset');
    });

    // Approval Policies (admin/manager)
    Route::get('/approval-policies', [ApprovalWebController::class, 'policies'])->name('approval-policies.index');
    Route::post('/approval-policies', [ApprovalWebController::class, 'storePolicy'])->name('approval-policies.store');
    Route::put('/approval-policies/{policy}', [ApprovalWebController::class, 'updatePolicy'])->name('approval-policies.update');
    Route::patch('/approval-policies/{policy}/toggle', [ApprovalWebController::class, 'togglePolicy'])->name('approval-policies.toggle');
    Route::delete('/approval-policies/{policy}', [ApprovalWebController::class, 'destroyPolicy'])->name('approval-policies.destroy');

    // Customer Requests
    Route::resource('requests', RequestWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::put('/requests/{request}/review', [RequestWebController::class, 'review'])->name('requests.review');
    Route::put('/requests/{request}/approve', [RequestWebController::class, 'approve'])->name('requests.approve');
    Route::put('/requests/{request}/reject', [RequestWebController::class, 'reject'])->name('requests.reject');

    // Campaigns & Leads
    Route::resource('campaigns', CampaignWebController::class);
    Route::post('/campaigns/{campaign}/leads', [CampaignWebController::class, 'storeLead'])->name('campaigns.leads.store');
    Route::put('/leads/{lead}', [CampaignWebController::class, 'updateLead'])->name('leads.update');
    Route::delete('/leads/{lead}', [CampaignWebController::class, 'destroyLead'])->name('leads.destroy');
    Route::post('/campaigns/{campaign}/leads/bulk', [CampaignWebController::class, 'bulkUpdateLeads'])->name('campaigns.leads.bulk');
    Route::patch('/campaigns/{campaign}/metrics', [CampaignWebController::class, 'updateMetrics'])->name('campaigns.metrics');

    // Invoices
    Route::resource('invoices', InvoiceWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::put('/invoices/{invoice}/send', [InvoiceWebController::class, 'send'])->name('invoices.send');
    Route::put('/invoices/{invoice}/mark-paid', [InvoiceWebController::class, 'markPaid'])->name('invoices.markPaid');
    Route::get('/invoices/{invoice}/pdf', [InvoiceWebController::class, 'downloadPdf'])->name('invoices.pdf');

    // Knowledge Base
    Route::get('/projects/{project}/kb', [KbArticleWebController::class, 'index'])->name('kb.index');
    Route::post('/projects/{project}/kb', [KbArticleWebController::class, 'store'])->name('kb.store');
    Route::get('/projects/{project}/kb/{article}', [KbArticleWebController::class, 'show'])->name('kb.show');
    Route::put('/projects/{project}/kb/{article}', [KbArticleWebController::class, 'update'])->name('kb.update');
    Route::delete('/projects/{project}/kb/{article}', [KbArticleWebController::class, 'destroy'])->name('kb.destroy');
    Route::delete('/kb-attachments/{attachment}', [KbArticleWebController::class, 'deleteAttachment'])->name('kb.attachment.destroy');

    // AJAX cascade dropdowns
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/companies',  [AjaxController::class, 'companies'])->name('companies');
        Route::get('/branches',   [AjaxController::class, 'branches'])->name('branches');
        Route::get('/divisions',  [AjaxController::class, 'divisions'])->name('divisions');
        Route::get('/departments',[AjaxController::class, 'departments'])->name('departments');
    });

    // User Management (Admin only)
    Route::resource('users', UserWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Client Management
    Route::resource('clients', ClientWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Master Data (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/master', [MasterDataWebController::class, 'index'])->name('master.index');
        Route::resource('companies', CompanyWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('branches', BranchWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('divisions', DivisionWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('departments', DepartmentWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('structural-levels', StructuralLevelWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('structural-levels/reset', [StructuralLevelWebController::class, 'resetDefault'])->name('structural-levels.reset');
    });

    // Timesheet
    Route::get('/projects/{project}/timesheet', [ProjectWebController::class, 'timesheet'])->name('projects.timesheet');

    // Workload
    Route::get('/workload', [DashboardWebController::class, 'workload'])->name('workload');

    // Calendar
    Route::get('/calendar', [CalendarWebController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarWebController::class, 'events'])->name('calendar.events');
    Route::get('/calendar/upcoming', [CalendarWebController::class, 'upcoming'])->name('calendar.upcoming');

    // Global Search
    Route::get('/search', [SearchWebController::class, 'index'])->name('search.index');

    // Analytics
    Route::get('/analytics', [AnalyticsWebController::class, 'index'])->name('analytics.index');

    // Sprints
    Route::get('/projects/{project}/sprints', [SprintWebController::class, 'index'])->name('sprints.index');
    Route::post('/projects/{project}/sprints', [SprintWebController::class, 'store'])->name('sprints.store');
    Route::get('/projects/{project}/sprints/{sprint}', [SprintWebController::class, 'show'])->name('sprints.show');
    Route::put('/projects/{project}/sprints/{sprint}', [SprintWebController::class, 'update'])->name('sprints.update');
    Route::delete('/projects/{project}/sprints/{sprint}', [SprintWebController::class, 'destroy'])->name('sprints.destroy');
    Route::post('/projects/{project}/sprints/{sprint}/tasks', [SprintWebController::class, 'addTask'])->name('sprints.tasks.add');
    Route::delete('/projects/{project}/sprints/{sprint}/tasks', [SprintWebController::class, 'removeTask'])->name('sprints.tasks.remove');

    // File Manager
    Route::get('/projects/{project}/files', [ProjectFileWebController::class, 'index'])->name('project.files.index');
    Route::post('/projects/{project}/files', [ProjectFileWebController::class, 'store'])->name('project.files.store');
    Route::delete('/projects/{project}/files/{projectFile}', [ProjectFileWebController::class, 'destroy'])->name('project.files.destroy');
    Route::patch('/projects/{project}/files/{projectFile}/folder', [ProjectFileWebController::class, 'moveFolder'])->name('project.files.move');

    // Budget
    Route::get('/projects/{project}/budget', [BudgetWebController::class, 'index'])->name('budget.index');
    Route::post('/projects/{project}/budget', [BudgetWebController::class, 'store'])->name('budget.store');
    Route::delete('/projects/{project}/budget/{budgetEntry}', [BudgetWebController::class, 'destroy'])->name('budget.destroy');
    Route::patch('/projects/{project}/budget/threshold', [BudgetWebController::class, 'updateThreshold'])->name('budget.threshold');

    // Risk Register
    Route::get('/projects/{project}/risks', [RiskWebController::class, 'index'])->name('risks.index');
    Route::post('/projects/{project}/risks', [RiskWebController::class, 'store'])->name('risks.store');
    Route::put('/projects/{project}/risks/{risk}', [RiskWebController::class, 'update'])->name('risks.update');
    Route::delete('/projects/{project}/risks/{risk}', [RiskWebController::class, 'destroy'])->name('risks.destroy');
    Route::get('/projects/{project}/risks/matrix', [RiskWebController::class, 'matrix'])->name('risks.matrix');

    // Recurring Tasks
    Route::get('/projects/{project}/recurring', [RecurringTaskWebController::class, 'index'])->name('recurring.index');
    Route::post('/projects/{project}/recurring', [RecurringTaskWebController::class, 'store'])->name('recurring.store');
    Route::put('/projects/{project}/recurring/{recurringTask}', [RecurringTaskWebController::class, 'update'])->name('recurring.update');
    Route::delete('/projects/{project}/recurring/{recurringTask}', [RecurringTaskWebController::class, 'destroy'])->name('recurring.destroy');

    // Client Portal Management
    Route::get('/projects/{project}/portal', [ClientPortalWebController::class, 'index'])->name('portal.index');
    Route::post('/projects/{project}/portal', [ClientPortalWebController::class, 'store'])->name('portal.store');
    Route::delete('/projects/{project}/portal/{portalToken}', [ClientPortalWebController::class, 'destroy'])->name('portal.destroy');

    // Project Templates
    Route::get('/templates', [ProjectTemplateWebController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [ProjectTemplateWebController::class, 'create'])->name('templates.create');
    Route::post('/templates', [ProjectTemplateWebController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}', [ProjectTemplateWebController::class, 'show'])->name('templates.show');
    Route::delete('/templates/{template}', [ProjectTemplateWebController::class, 'destroy'])->name('templates.destroy');
    Route::get('/templates/{template}/apply', [ProjectTemplateWebController::class, 'applyForm'])->name('templates.apply');
    Route::post('/templates/{template}/apply', [ProjectTemplateWebController::class, 'applyToProject'])->name('templates.apply.post');

    // Chat
    Route::get('/chat', [ChatWebController::class, 'index'])->name('chat.index');
    Route::get('/chat/unread', [ChatWebController::class, 'unreadCount'])->name('chat.unread');
    Route::get('/projects/{project}/chat/messages', [ChatWebController::class, 'messages'])->name('chat.messages');
    Route::get('/projects/{project}/chat/members', [ChatWebController::class, 'members'])->name('chat.members');
    Route::post('/projects/{project}/chat', [ChatWebController::class, 'store'])->name('chat.store');
    Route::put('/projects/{project}/chat/{message}', [ChatWebController::class, 'update'])->name('chat.update');
    Route::delete('/projects/{project}/chat/{message}', [ChatWebController::class, 'destroy'])->name('chat.destroy');
    Route::post('/projects/{project}/chat/{message}/react', [ChatWebController::class, 'react'])->name('chat.react');
    Route::post('/projects/{project}/chat/read', [ChatWebController::class, 'markRead'])->name('chat.markRead');

    // Exports
    Route::get('/projects/{project}/export/timesheet/excel', [ExportWebController::class, 'timesheetExcel'])->name('export.timesheet.excel');
    Route::get('/projects/{project}/export/timesheet/pdf', [ExportWebController::class, 'timesheetPdf'])->name('export.timesheet.pdf');
    Route::get('/projects/{project}/export/report/pdf', [ExportWebController::class, 'projectReportPdf'])->name('export.report.pdf');
    Route::get('/projects/{project}/export/report/excel', [ExportWebController::class, 'projectReportExcel'])->name('export.report.excel');

    // ─── HRIS ─────────────────────────────────────────────────────────────────
    Route::prefix('hris')->name('hris.')->middleware('package:hris')->group(function () {

        // Absensi
        Route::get('absensi',                            [AbsensiController::class, 'index'])->name('absensi.index');
        Route::post('absensi/checkin',                   [AbsensiController::class, 'checkIn'])->name('absensi.checkin');
        Route::post('absensi/checkout',                  [AbsensiController::class, 'checkOut'])->name('absensi.checkout');
        Route::get('absensi/rekap',                      [AbsensiController::class, 'rekap'])->name('absensi.rekap');
        Route::get('absensi/setting',                    [AbsensiController::class, 'setting'])->name('absensi.setting');
        Route::post('absensi/setting',                   [AbsensiController::class, 'saveSetting'])->name('absensi.setting.save');
        Route::post('absensi/enroll-face/{employee}',    [AbsensiController::class, 'enrollFace'])->name('absensi.enroll-face');
        Route::delete('absensi/delete-face/{employee}',  [AbsensiController::class, 'deleteFace'])->name('absensi.delete-face');

        // Cuti & Izin
        Route::get('leave',                        [LeaveController::class, 'index'])->name('leave.index');
        Route::get('leave/create',                 [LeaveController::class, 'create'])->name('leave.create');
        Route::post('leave',                       [LeaveController::class, 'store'])->name('leave.store');
        Route::delete('leave/{leave}',             [LeaveController::class, 'destroy'])->name('leave.destroy');
        Route::patch('leave/{leave}/approve',      [LeaveController::class, 'approve'])->name('leave.approve');
        Route::patch('leave/{leave}/reject',       [LeaveController::class, 'reject'])->name('leave.reject');

        // Lembur
        Route::get('overtime',                     [OvertimeController::class, 'index'])->name('overtime.index');
        Route::get('overtime/create',              [OvertimeController::class, 'create'])->name('overtime.create');
        Route::post('overtime',                    [OvertimeController::class, 'store'])->name('overtime.store');
        Route::delete('overtime/{overtime}',       [OvertimeController::class, 'destroy'])->name('overtime.destroy');
        Route::patch('overtime/{overtime}/approve',[OvertimeController::class, 'approve'])->name('overtime.approve');

        // Reimburse
        Route::get('reimburse',                        [ReimbursementController::class, 'index'])->name('reimburse.index');
        Route::get('reimburse/create',                 [ReimbursementController::class, 'create'])->name('reimburse.create');
        Route::post('reimburse',                       [ReimbursementController::class, 'store'])->name('reimburse.store');
        Route::delete('reimburse/{reimburse}',         [ReimbursementController::class, 'destroy'])->name('reimburse.destroy');
        Route::patch('reimburse/{reimburse}/approve',  [ReimbursementController::class, 'approve'])->name('reimburse.approve');

        // Gaji Karyawan (EmployeeSalary)
        Route::get('salary/{user}',              [EmployeeSalaryController::class, 'index'])->name('salary.index');
        Route::get('salary/{user}/create',       [EmployeeSalaryController::class, 'create'])->name('salary.create');
        Route::post('salary/{user}',             [EmployeeSalaryController::class, 'store'])->name('salary.store');
        Route::get('salary/{user}/{salary}/edit',[EmployeeSalaryController::class, 'edit'])->name('salary.edit');
        Route::put('salary/{user}/{salary}',     [EmployeeSalaryController::class, 'update'])->name('salary.update');
        Route::delete('salary/{user}/{salary}',  [EmployeeSalaryController::class, 'destroy'])->name('salary.destroy');

        // Payroll
        Route::get('payroll',                        [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate',              [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{payroll}',              [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('payroll/{payroll}/slip',         [PayrollController::class, 'cetakSlip'])->name('payroll.slip');
        Route::patch('payroll/{payroll}/finalize',   [PayrollController::class, 'finalize'])->name('payroll.finalize');

        // Master Data HRIS
        Route::prefix('master')->name('master.')->middleware('can:manage hris master')->group(function () {
            Route::get('/',                                    [HrisMasterController::class, 'index'])->name('index');

            Route::post('leave-types',                         [LeaveTypeController::class, 'store'])->name('leave-types.store');
            Route::put('leave-types/{leaveType}',              [LeaveTypeController::class, 'update'])->name('leave-types.update');
            Route::delete('leave-types/{leaveType}',           [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
            Route::patch('leave-types/{leaveType}/toggle',     [LeaveTypeController::class, 'toggle'])->name('leave-types.toggle');
            Route::post('leave-types/reset',                   [LeaveTypeController::class, 'resetDefault'])->name('leave-types.reset');

            Route::post('overtime-rules',                      [OvertimeRuleController::class, 'store'])->name('overtime-rules.store');
            Route::put('overtime-rules/{overtimeRule}',        [OvertimeRuleController::class, 'update'])->name('overtime-rules.update');
            Route::delete('overtime-rules/{overtimeRule}',     [OvertimeRuleController::class, 'destroy'])->name('overtime-rules.destroy');
            Route::patch('overtime-rules/{overtimeRule}/toggle',[OvertimeRuleController::class, 'toggle'])->name('overtime-rules.toggle');
            Route::post('overtime-rules/reset',                [OvertimeRuleController::class, 'resetDefault'])->name('overtime-rules.reset');

            Route::patch('tax-ptkp/{taxPtkp}',                 [TaxPtkpController::class, 'update'])->name('tax-ptkp.update');
            Route::patch('tax-ptkp/{taxPtkp}/toggle',          [TaxPtkpController::class, 'toggle'])->name('tax-ptkp.toggle');
            Route::post('tax-ptkp/reset',                      [TaxPtkpController::class, 'resetDefault'])->name('tax-ptkp.reset');

            Route::post('tax-brackets',                        [TaxBracketController::class, 'store'])->name('tax-brackets.store');
            Route::get('tax-brackets/{taxBracket}/edit',       [TaxBracketController::class, 'edit'])->name('tax-brackets.edit');
            Route::put('tax-brackets/{taxBracket}',            [TaxBracketController::class, 'update'])->name('tax-brackets.update');
            Route::delete('tax-brackets/{taxBracket}',         [TaxBracketController::class, 'destroy'])->name('tax-brackets.destroy');
            Route::patch('tax-brackets/{taxBracket}/toggle',   [TaxBracketController::class, 'toggle'])->name('tax-brackets.toggle');
            Route::post('tax-brackets/reset',                  [TaxBracketController::class, 'resetDefault'])->name('tax-brackets.reset');
        });
    });

}); // end Route::middleware(['auth', 'check.active'])

// Client Portal (no auth — token based)
Route::get('/portal/{token}', [ClientPortalWebController::class, 'view'])->name('portal.view');
Route::post('/portal/{token}/comment', [ClientPortalWebController::class, 'comment'])->name('portal.comment');
