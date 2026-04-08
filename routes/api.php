<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BugTicketController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KbArticleController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\TaskController;
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

    // ─── Admin / Manager only ────────────────────────────────────────────────
    Route::middleware('role:admin|manager')->group(function () {

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

        // Requests: approve/reject
        Route::put('/requests/{customerRequest}/approve', [CustomerRequestController::class, 'approve']);
        Route::put('/requests/{customerRequest}/reject', [CustomerRequestController::class, 'reject']);

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
    Route::get('/tickets/{ticket}', [BugTicketController::class, 'show']);
    Route::put('/tickets/{ticket}/status', [BugTicketController::class, 'updateStatus']);
    Route::post('/tickets/{ticket}/comments', [BugTicketController::class, 'addComment']);
    Route::get('/tickets/{ticket}/history', [BugTicketController::class, 'history']);
    Route::put('/tickets/{ticket}/reopen', [BugTicketController::class, 'reopen']);
    Route::get('/projects/{project}/sla-report', [BugTicketController::class, 'slaReport']);

    // ─── Customer Requests ───────────────────────────────────────────────────
    Route::get('/requests', [CustomerRequestController::class, 'index']);
    Route::post('/requests', [CustomerRequestController::class, 'store']);
    Route::get('/requests/{customerRequest}', [CustomerRequestController::class, 'show']);
    Route::put('/requests/{customerRequest}/review', [CustomerRequestController::class, 'review']);

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

    // ─── Knowledge Base ──────────────────────────────────────────────────────
    Route::get('/projects/{project}/kb', [KbArticleController::class, 'index']);
    Route::post('/projects/{project}/kb', [KbArticleController::class, 'store']);
    Route::get('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'show']);
    Route::put('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'update']);
    Route::delete('/projects/{project}/kb/{kbArticle}', [KbArticleController::class, 'destroy']);
});
