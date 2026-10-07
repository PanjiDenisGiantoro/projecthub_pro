<?php

use App\Http\Controllers\Api\ProjectWorkspaceController;
use App\Http\Controllers\Web\CalendarWebController;
use App\Http\Controllers\Web\ChatWebController;
use App\Http\Controllers\Web\DirectMessageWebController;
use App\Http\Controllers\Web\ForumWebController;
use App\Http\Controllers\Api\Hris\AttendanceController;
use App\Http\Controllers\Api\Hris\HrisAdminController;
use App\Http\Controllers\Api\Hris\LeaveController as HrisLeaveController;
use App\Http\Controllers\Api\Hris\OvertimeController as HrisOvertimeController;
use App\Http\Controllers\Api\Hris\PayrollController as HrisPayrollController;
use App\Http\Controllers\Api\Hris\ReimbursementController as HrisReimbursementController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BugTicketController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KbArticleController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationUnitController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TicketChecklistController;
use App\Http\Controllers\TicketTemplateController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public Auth ─────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// ─── Protected Routes ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/workload', [DashboardController::class, 'workload']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // ─── Admin only — Master Data ────────────────────────────────────────────
    Route::middleware('role:admin')->name('api.')->group(function () {

        // Companies
        Route::apiResource('companies', CompanyController::class);

        // Organization units (write: admin only)
        Route::apiResource('organization-units', OrganizationUnitController::class)->except(['index', 'show']);
    });

    // ─── Admin / Member only ─────────────────────────────────────────────────
    Route::middleware('role:admin|member')->group(function () {

        // Organization units (read: admin + manager, for user form dropdown)
        Route::get('/organization-units', [OrganizationUnitController::class, 'index']);
        Route::get('/organization-units/options', [OrganizationUnitController::class, 'options']);
        Route::get('/organization-units/{organizationUnit}', [OrganizationUnitController::class, 'show']);

        // User management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::get('/roles', [UserController::class, 'roles']);

        // SLA Policies
        Route::get('/sla-policies', [SlaPolicyController::class, 'index']);
        Route::post('/sla-policies', [SlaPolicyController::class, 'store']);
        Route::put('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'update']);
        Route::delete('/sla-policies/{slaPolicy}', [SlaPolicyController::class, 'destroy']);

        // Tickets: breached overview
        Route::get('/tickets/breached', [BugTicketController::class, 'breached']);

        // Approvals: admin/manager all
        Route::get('/approvals', [ApprovalController::class, 'index']);

        // Approval Policies — master data
        Route::get('/approval-policies', [ApprovalController::class, 'policies']);
        Route::post('/approval-policies', [ApprovalController::class, 'storePolicy']);
        Route::get('/approval-policies/{policy}', [ApprovalController::class, 'showPolicy']);
        Route::put('/approval-policies/{policy}', [ApprovalController::class, 'updatePolicy']);
        Route::patch('/approval-policies/{policy}/toggle', [ApprovalController::class, 'togglePolicy']);
        Route::delete('/approval-policies/{policy}', [ApprovalController::class, 'destroyPolicy']);

        // Project management
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/members', [ProjectController::class, 'addMember']);
        Route::delete('/projects/{project}/members/{userId}', [ProjectController::class, 'removeMember']);

        // Task management
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy']);

        // Tickets: assign
        Route::put('/tickets/{ticket}/assign', [BugTicketController::class, 'assign']);

        // Requests: approve/reject/complete
        Route::put('/requests/{customerRequest}/approve', [CustomerRequestController::class, 'approve']);
        Route::put('/requests/{customerRequest}/reject', [CustomerRequestController::class, 'reject']);
        Route::put('/requests/{customerRequest}/complete', [CustomerRequestController::class, 'complete']);

        // Invoices
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::put('/invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::put('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);
    });

    // ─── Projects ────────────────────────────────────────────────────────────
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    // Milestones
    Route::get('/projects/{project}/milestones', [MilestoneController::class, 'index']);
    Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store']);
    Route::put('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'update']);
    Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy']);

    // Tasks
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::get('/projects/{project}/tasks/{task}', [TaskController::class, 'show']);

    // ─── Bug Tickets ─────────────────────────────────────────────────────────
    Route::get('/projects/{project}/tickets', [BugTicketController::class, 'index']);
    Route::post('/projects/{project}/tickets', [BugTicketController::class, 'store']);
    Route::post('/projects/{project}/tickets/bulk', [BugTicketController::class, 'bulkUpdate']);
    Route::get('/projects/{project}/tickets/export', [BugTicketController::class, 'export']);
    Route::get('/projects/{project}/tickets/aging', [BugTicketController::class, 'agingReport']);
    Route::get('/projects/{project}/tickets/workload', [BugTicketController::class, 'workloadReport']);
    Route::get('/projects/{project}/tickets/trend', [BugTicketController::class, 'trendReport']);
    Route::get('/projects/{project}/sla-report', [BugTicketController::class, 'slaReport']);

    Route::get('/tickets/{ticket}', [BugTicketController::class, 'show']);
    Route::put('/tickets/{ticket}', [BugTicketController::class, 'update']);
    Route::put('/tickets/{ticket}/status', [BugTicketController::class, 'updateStatus']);
    Route::put('/tickets/{ticket}/reopen', [BugTicketController::class, 'reopen']);
    Route::put('/tickets/{ticket}/merge', [BugTicketController::class, 'mergeInto']);
    Route::put('/tickets/{ticket}/sla/pause', [BugTicketController::class, 'pauseSla']);
    Route::put('/tickets/{ticket}/sla/resume', [BugTicketController::class, 'resumeSla']);
    Route::post('/tickets/{ticket}/request-escalation', [BugTicketController::class, 'requestEscalation']);
    Route::post('/tickets/{ticket}/request-sla-extension', [BugTicketController::class, 'requestSlaExtension']);
    Route::post('/tickets/{ticket}/request-security-disclose', [BugTicketController::class, 'requestSecurityDisclose']);

    Route::post('/tickets/{ticket}/comments', [BugTicketController::class, 'addComment']);
    Route::get('/tickets/{ticket}/history', [BugTicketController::class, 'history']);

    Route::get('/tickets/{ticket}/watchers', [BugTicketController::class, 'listWatchers']);
    Route::post('/tickets/{ticket}/watch', [BugTicketController::class, 'watch']);
    Route::delete('/tickets/{ticket}/watch', [BugTicketController::class, 'unwatch']);

    Route::get('/tickets/{ticket}/linked', [BugTicketController::class, 'linkedTickets']);
    Route::post('/tickets/{ticket}/links', [BugTicketController::class, 'linkTicket']);
    Route::delete('/tickets/{ticket}/links/{link}', [BugTicketController::class, 'unlinkTicket']);

    Route::get('/tickets/{ticket}/checklists', [TicketChecklistController::class, 'index']);
    Route::post('/tickets/{ticket}/checklists', [TicketChecklistController::class, 'store']);
    Route::put('/tickets/{ticket}/checklists/{item}', [TicketChecklistController::class, 'update']);
    Route::put('/tickets/{ticket}/checklists/{item}/toggle', [TicketChecklistController::class, 'toggle']);
    Route::delete('/tickets/{ticket}/checklists/{item}', [TicketChecklistController::class, 'destroy']);

    // ─── Ticket Templates ────────────────────────────────────────────────────
    Route::get('/ticket-templates', [TicketTemplateController::class, 'index']);
    Route::post('/ticket-templates', [TicketTemplateController::class, 'store']);
    Route::get('/ticket-templates/{template}', [TicketTemplateController::class, 'show']);
    Route::put('/ticket-templates/{template}', [TicketTemplateController::class, 'update']);
    Route::delete('/ticket-templates/{template}', [TicketTemplateController::class, 'destroy']);

    // ─── Approvals ───────────────────────────────────────────────────────────
    Route::get('/approvals/pending-for-me', [ApprovalController::class, 'pendingForMe']);
    Route::get('/approvals/mine', [ApprovalController::class, 'mine']);
    Route::get('/approvals/{approval}', [ApprovalController::class, 'show']);
    Route::put('/approvals/{approval}/approve', [ApprovalController::class, 'approve']);
    Route::put('/approvals/{approval}/reject', [ApprovalController::class, 'reject']);
    Route::delete('/approvals/{approval}', [ApprovalController::class, 'cancel']);

    // ─── Customer Requests ───────────────────────────────────────────────────
    Route::get('/requests', [CustomerRequestController::class, 'index']);
    Route::post('/requests', [CustomerRequestController::class, 'store']);
    Route::get('/requests/{customerRequest}', [CustomerRequestController::class, 'show']);

    // ─── Marketing ───────────────────────────────────────────────────────────
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);
    Route::post('/campaigns/{campaign}/leads', [CampaignController::class, 'storeLead']);
    Route::get('/leads', [CampaignController::class, 'leads']);
    Route::put('/leads/{lead}', [CampaignController::class, 'updateLead']);

    // ─── Time Tracking ───────────────────────────────────────────────────────
    Route::post('/tasks/{task}/time-logs', [TimeLogController::class, 'store']);
    Route::get('/projects/{project}/timesheet', [TimeLogController::class, 'timesheet']);

    // ─── Invoices (read) ─────────────────────────────────────────────────────
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);

    // ─── Proyek: sprint, file, budget, risiko, anggota, komentar task ──────
    Route::get('/projects/{project}/sprints', [ProjectWorkspaceController::class, 'sprints']);
    Route::post('/projects/{project}/sprints', [ProjectWorkspaceController::class, 'storeSprint']);
    Route::put('/projects/{project}/sprints/{sprint}', [ProjectWorkspaceController::class, 'updateSprint']);
    Route::delete('/projects/{project}/sprints/{sprint}', [ProjectWorkspaceController::class, 'destroySprint']);
    Route::get('/projects/{project}/files', [ProjectWorkspaceController::class, 'files']);
    Route::post('/projects/{project}/files', [ProjectWorkspaceController::class, 'storeFile']);
    Route::delete('/projects/{project}/files/{projectFile}', [ProjectWorkspaceController::class, 'destroyFile']);
    Route::get('/projects/{project}/budget', [ProjectWorkspaceController::class, 'budget']);
    Route::post('/projects/{project}/budget', [ProjectWorkspaceController::class, 'storeBudget']);
    Route::delete('/projects/{project}/budget/{budgetEntry}', [ProjectWorkspaceController::class, 'destroyBudget']);
    Route::patch('/projects/{project}/budget/threshold', [ProjectWorkspaceController::class, 'budgetThreshold']);
    Route::get('/projects/{project}/risks', [ProjectWorkspaceController::class, 'risks']);
    Route::post('/projects/{project}/risks', [ProjectWorkspaceController::class, 'storeRisk']);
    Route::put('/projects/{project}/risks/{risk}', [ProjectWorkspaceController::class, 'updateRisk']);
    Route::delete('/projects/{project}/risks/{risk}', [ProjectWorkspaceController::class, 'destroyRisk']);
    Route::post('/projects/{project}/team', [ProjectWorkspaceController::class, 'addMembers']);
    Route::delete('/projects/{project}/team/{user}', [ProjectWorkspaceController::class, 'removeMember']);
    Route::get('/projects/{project}/tasks/{task}/comments', [ProjectWorkspaceController::class, 'taskComments']);
    Route::post('/projects/{project}/tasks/{task}/comments', [ProjectWorkspaceController::class, 'storeTaskComment']);

    // ─── Kalender (controller sama dengan web, sudah JSON) ────────────────
    Route::get('/calendar/events', [CalendarWebController::class, 'events']);
    Route::get('/calendar/upcoming', [CalendarWebController::class, 'upcoming']);

    // ─── Chat (controller sama dengan web, semuanya sudah JSON) ─────────────
    Route::get('/chat/inbox', [ChatWebController::class, 'inbox']);
    Route::get('/chat/unread', [ChatWebController::class, 'unreadCount']);
    Route::get('/projects/{project}/chat/messages', [ChatWebController::class, 'messages']);
    Route::get('/projects/{project}/chat/members', [ChatWebController::class, 'members']);
    Route::post('/projects/{project}/chat', [ChatWebController::class, 'store']);
    Route::put('/projects/{project}/chat/{message}', [ChatWebController::class, 'update']);
    Route::delete('/projects/{project}/chat/{message}', [ChatWebController::class, 'destroy']);
    Route::post('/projects/{project}/chat/{message}/react', [ChatWebController::class, 'react']);
    Route::post('/projects/{project}/chat/read', [ChatWebController::class, 'markRead']);
    Route::get('/messages/{peer}/thread', [DirectMessageWebController::class, 'messages']);
    Route::post('/messages/{peer}', [DirectMessageWebController::class, 'store']);
    Route::put('/messages/{peer}/{message}', [DirectMessageWebController::class, 'update']);
    Route::delete('/messages/{peer}/{message}', [DirectMessageWebController::class, 'destroy']);
    Route::post('/messages/{peer}/read', [DirectMessageWebController::class, 'markRead']);
    Route::post('/forums', [ForumWebController::class, 'store']);
    Route::post('/forums/{forum}/members', [ForumWebController::class, 'addMember']);
    Route::delete('/forums/{forum}/members/{user}', [ForumWebController::class, 'removeMember']);
    Route::get('/forums/{forum}/members', [ForumWebController::class, 'members']);
    Route::get('/forums/{forum}/messages', [ForumWebController::class, 'messages']);
    Route::post('/forums/{forum}/messages', [ForumWebController::class, 'storeMessage']);
    Route::put('/forums/{forum}/messages/{message}', [ForumWebController::class, 'update']);
    Route::delete('/forums/{forum}/messages/{message}', [ForumWebController::class, 'destroy']);
    Route::post('/forums/{forum}/read', [ForumWebController::class, 'markRead']);

    // ─── HRIS (mobile) ──────────────────────────────────────────────────────
    Route::prefix('hris')->middleware('package:hris')->group(function () {
        Route::get('/me', [HrisAdminController::class, 'me']);

        // Absensi
        Route::get('/attendance/today', [AttendanceController::class, 'today']);
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/attendance/history', [AttendanceController::class, 'history']);
        Route::get('/attendance/team', [AttendanceController::class, 'team']);
        Route::get('/attendance/holidays', [AttendanceController::class, 'holidays']);
        Route::post('/attendance/holidays', [HrisAdminController::class, 'storeHoliday']);
        Route::delete('/attendance/holidays/{holiday}', [HrisAdminController::class, 'destroyHoliday']);
        Route::get('/attendance/setting', [HrisAdminController::class, 'setting']);
        Route::put('/attendance/setting', [HrisAdminController::class, 'saveSetting']);
        Route::get('/attendance/schedules', [HrisAdminController::class, 'schedules']);
        Route::post('/attendance/schedules', [HrisAdminController::class, 'saveSchedule']);

        // Shift kerja
        Route::get('/shifts', [HrisAdminController::class, 'shifts']);
        Route::post('/shifts', [HrisAdminController::class, 'storeShift']);
        Route::put('/shifts/{shift}', [HrisAdminController::class, 'updateShift']);
        Route::patch('/shifts/{shift}/toggle', [HrisAdminController::class, 'toggleShift']);
        Route::delete('/shifts/{shift}', [HrisAdminController::class, 'destroyShift']);

        // Karyawan & gaji
        Route::get('/employees', [HrisAdminController::class, 'employees']);
        Route::put('/employees/{employee}/shift', [HrisAdminController::class, 'assignShift']);
        Route::get('/employees/{user}/salaries', [HrisPayrollController::class, 'salaries']);
        Route::post('/employees/{user}/salaries', [HrisPayrollController::class, 'storeSalary']);
        Route::put('/employees/{user}/salaries/{salary}', [HrisPayrollController::class, 'updateSalary']);

        // Cuti
        Route::get('/leave-types', [HrisLeaveController::class, 'types']);
        Route::get('/leaves', [HrisLeaveController::class, 'index']);
        Route::post('/leaves', [HrisLeaveController::class, 'store']);
        Route::put('/leaves/{leave}', [HrisLeaveController::class, 'update']);
        Route::delete('/leaves/{leave}', [HrisLeaveController::class, 'destroy']);
        Route::patch('/leaves/{leave}/approve', [HrisLeaveController::class, 'approve']);
        Route::patch('/leaves/{leave}/reject', [HrisLeaveController::class, 'reject']);

        // Lembur
        Route::get('/overtimes', [HrisOvertimeController::class, 'index']);
        Route::post('/overtimes/preview', [HrisOvertimeController::class, 'preview']);
        Route::post('/overtimes', [HrisOvertimeController::class, 'store']);
        Route::put('/overtimes/{overtime}', [HrisOvertimeController::class, 'update']);
        Route::delete('/overtimes/{overtime}', [HrisOvertimeController::class, 'destroy']);
        Route::patch('/overtimes/{overtime}/approve', [HrisOvertimeController::class, 'approve']);
        Route::patch('/overtimes/{overtime}/reject', [HrisOvertimeController::class, 'reject']);

        // Reimburse
        Route::get('/reimbursements', [HrisReimbursementController::class, 'index']);
        Route::post('/reimbursements', [HrisReimbursementController::class, 'store']);
        Route::put('/reimbursements/{reimburse}', [HrisReimbursementController::class, 'update']);
        Route::delete('/reimbursements/{reimburse}', [HrisReimbursementController::class, 'destroy']);
        Route::patch('/reimbursements/{reimburse}/approve', [HrisReimbursementController::class, 'approve']);
        Route::patch('/reimbursements/{reimburse}/reject', [HrisReimbursementController::class, 'reject']);

        // Payroll, bonus, kasbon
        Route::get('/payrolls', [HrisPayrollController::class, 'index']);
        Route::post('/payrolls/generate', [HrisPayrollController::class, 'generate']);
        Route::get('/payrolls/{payroll}', [HrisPayrollController::class, 'show']);
        Route::patch('/payrolls/{payroll}/finalize', [HrisPayrollController::class, 'finalize']);
        Route::get('/bonuses', [HrisPayrollController::class, 'bonuses']);
        Route::post('/bonuses', [HrisPayrollController::class, 'storeBonus']);
        Route::delete('/bonuses/{bonus}', [HrisPayrollController::class, 'destroyBonus']);
        Route::get('/kasbons', [HrisPayrollController::class, 'kasbons']);
        Route::post('/kasbons', [HrisPayrollController::class, 'storeKasbon']);
        Route::delete('/kasbons/{kasbon}', [HrisPayrollController::class, 'destroyKasbon']);
    });

    // ─── Knowledge Base ──────────────────────────────────────────────────────
    Route::get('/projects/{project}/kb', [KbArticleController::class, 'index']);
    Route::post('/projects/{project}/kb', [KbArticleController::class, 'store']);
    Route::get('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'show']);
    Route::put('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'update']);
    Route::delete('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'destroy']);
});
