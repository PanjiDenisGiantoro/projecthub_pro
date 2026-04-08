<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CampaignWebController;
use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\KbArticleWebController;
use App\Http\Controllers\Web\MilestoneWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\RequestWebController;
use App\Http\Controllers\Web\TaskWebController;
use App\Http\Controllers\Web\TicketWebController;
use App\Http\Controllers\Web\UserWebController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardWebController::class, 'index'])->name('dashboard');

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

    // Customer Requests
    Route::resource('requests', RequestWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::put('/requests/{request}/review', [RequestWebController::class, 'review'])->name('requests.review');
    Route::put('/requests/{request}/approve', [RequestWebController::class, 'approve'])->name('requests.approve');
    Route::put('/requests/{request}/reject', [RequestWebController::class, 'reject'])->name('requests.reject');

    // Campaigns & Leads
    Route::resource('campaigns', CampaignWebController::class);
    Route::post('/campaigns/{campaign}/leads', [CampaignWebController::class, 'storeLead'])->name('campaigns.leads.store');
    Route::put('/leads/{lead}', [CampaignWebController::class, 'updateLead'])->name('leads.update');

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

    // User Management (Admin only)
    Route::resource('users', UserWebController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Timesheet
    Route::get('/projects/{project}/timesheet', [ProjectWebController::class, 'timesheet'])->name('projects.timesheet');

    // Workload
    Route::get('/workload', [DashboardWebController::class, 'workload'])->name('workload');
});
