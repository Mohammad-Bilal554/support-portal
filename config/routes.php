<?php
declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

// ── Auth (public) ──────────────────────────────────────────────────────────
$router->group(['prefix' => 'auth'], function (Router $r) {
    $r->get('login',            [\App\Controllers\Auth\LoginController::class,          'showLogin'])->name('auth.login');
    $r->post('login',           [\App\Controllers\Auth\LoginController::class,          'login'])->name('auth.login.post');
    $r->get('logout',           [\App\Controllers\Auth\LogoutController::class,         'logout'])->name('auth.logout');
    $r->get('forgot-password',  [\App\Controllers\Auth\ForgotPasswordController::class, 'show'])->name('auth.forgot');
    $r->post('forgot-password', [\App\Controllers\Auth\ForgotPasswordController::class, 'send'])->name('auth.forgot.post');
    $r->get('reset-password/{token}',  [\App\Controllers\Auth\ResetPasswordController::class, 'show'])->name('auth.reset');
    $r->post('reset-password/{token}', [\App\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('auth.reset.post');
});

// ── Dashboard (authenticated) ─────────────────────────────────────────────
$router->get('dashboard', [\App\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard')->middleware(['auth']);

// ── Admin (authenticated + admin/employee role) ────────────────────────────
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:super_admin,employee']], function (Router $r) {

    // Dashboard alias
    $r->get('dashboard', [\App\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Users
    $r->get('users',              [\App\Controllers\Admin\UserController::class, 'index'])       ->name('admin.users.index');
    $r->get('users/create',       [\App\Controllers\Admin\UserController::class, 'create'])      ->name('admin.users.create');
    $r->post('users',             [\App\Controllers\Admin\UserController::class, 'store'])       ->name('admin.users.store');
    $r->get('users/{id}',         [\App\Controllers\Admin\UserController::class, 'edit'])        ->name('admin.users.edit');
    $r->post('users/{id}',        [\App\Controllers\Admin\UserController::class, 'update'])      ->name('admin.users.update');
    $r->delete('users/{id}',      [\App\Controllers\Admin\UserController::class, 'destroy'])     ->name('admin.users.destroy');
    $r->post('users/{id}/toggle', [\App\Controllers\Admin\UserController::class, 'toggleStatus'])->name('admin.users.toggle');

    // Companies
    $r->get('companies',              [\App\Controllers\Admin\CompanyController::class, 'index'])  ->name('admin.companies.index');
    $r->get('companies/create',       [\App\Controllers\Admin\CompanyController::class, 'create']) ->name('admin.companies.create');
    $r->post('companies',             [\App\Controllers\Admin\CompanyController::class, 'store'])  ->name('admin.companies.store');
    $r->get('companies/{id}',         [\App\Controllers\Admin\CompanyController::class, 'edit'])   ->name('admin.companies.edit');
    $r->post('companies/{id}',        [\App\Controllers\Admin\CompanyController::class, 'update']) ->name('admin.companies.update');
    $r->delete('companies/{id}',      [\App\Controllers\Admin\CompanyController::class, 'destroy'])->name('admin.companies.destroy');
    $r->post('companies/{id}/toggle', [\App\Controllers\Admin\CompanyController::class, 'toggle']) ->name('admin.companies.toggle');

    // Reports
    $r->get('reports',              [\App\Controllers\Admin\ReportController::class, 'index'])       ->name('admin.reports');
    $r->get('reports/export/pdf',   [\App\Controllers\Admin\ReportController::class, 'exportPdf'])   ->name('admin.reports.pdf');
    $r->get('reports/export/excel', [\App\Controllers\Admin\ReportController::class, 'exportExcel']) ->name('admin.reports.excel');

    // Settings & Logs
    $r->get('settings', [\App\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings');
    $r->post('settings',[\App\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
    $r->get('logs',     [\App\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.logs');

    // Email test (local env only)
    $r->get('email-test',  [\App\Controllers\Admin\EmailTestController::class, 'show'])->name('admin.email.test');
    $r->post('email-test', [\App\Controllers\Admin\EmailTestController::class, 'send'])->name('admin.email.test.post');
});

// ── Tickets (authenticated) ────────────────────────────────────────────────
$router->group(['prefix' => 'tickets', 'middleware' => ['auth']], function (Router $r) {
    $r->get('',            [\App\Controllers\Ticket\TicketController::class,      'index'])       ->name('tickets.index');
    $r->get('create',      [\App\Controllers\Ticket\TicketController::class,      'create'])      ->name('tickets.create');
    $r->post('',           [\App\Controllers\Ticket\TicketController::class,      'store'])       ->name('tickets.store');
    $r->get('{id}',        [\App\Controllers\Ticket\TicketController::class,      'show'])        ->name('tickets.show');
    $r->get('{id}/edit',   [\App\Controllers\Ticket\TicketController::class,      'edit'])        ->name('tickets.edit');
    $r->post('{id}',       [\App\Controllers\Ticket\TicketController::class,      'update'])      ->name('tickets.update');
    $r->delete('{id}',     [\App\Controllers\Ticket\TicketController::class,      'destroy'])     ->name('tickets.destroy');
    $r->post('{id}/status',[\App\Controllers\Ticket\TicketController::class,      'changeStatus'])->name('tickets.status');
    $r->post('{id}/assign',[\App\Controllers\Ticket\TicketController::class,      'assign'])      ->name('tickets.assign');
    $r->post('{id}/reply', [\App\Controllers\Ticket\ConversationController::class,'reply'])       ->name('tickets.reply');
    $r->delete('conversations/{id}', [\App\Controllers\Ticket\ConversationController::class,'destroy'])->name('tickets.conv.destroy');
    $r->get('attachments/{id}/download', [\App\Controllers\Ticket\AttachmentController::class,'download'])->name('tickets.attach.download');
    $r->delete('attachments/{id}',       [\App\Controllers\Ticket\AttachmentController::class,'destroy']) ->name('tickets.attach.destroy');
});

// ── In-app Notifications (authenticated) ───────────────────────────────────
$router->group(['prefix' => 'api', 'middleware' => ['auth']], function (Router $r) {
    $r->get('notifications/unread-count', [\App\Controllers\Api\NotificationController::class, 'unreadCount']);
    $r->get('notifications',              [\App\Controllers\Api\NotificationController::class, 'index']);
    $r->post('notifications/{id}/read',   [\App\Controllers\Api\NotificationController::class, 'markRead']);
    $r->post('notifications/read-all',    [\App\Controllers\Api\NotificationController::class, 'markAllRead']);
});

// ── REST API v1 (token auth) ───────────────────────────────────────────────

// Token generation — no auth middleware
$router->group(['prefix' => 'api/v1/auth'], function (Router $r) {
    $r->post('token', [\App\Controllers\Api\AuthApiController::class, 'token'])->name('api.auth.token');
});

// Token-authenticated endpoints
$router->group(['prefix' => 'api/v1', 'middleware' => ['api_auth']], function (Router $r) {

    // Auth management
    $r->post('auth/revoke', [\App\Controllers\Api\AuthApiController::class, 'revoke'])->name('api.auth.revoke');
    $r->get('auth/tokens',  [\App\Controllers\Api\AuthApiController::class, 'tokens'])->name('api.auth.tokens');

    // Tickets
    $r->get('tickets',                [\App\Controllers\Api\TicketApiController::class, 'index'])       ->name('api.tickets.index');
    $r->post('tickets',               [\App\Controllers\Api\TicketApiController::class, 'store'])       ->name('api.tickets.store');
    $r->get('tickets/{id}',           [\App\Controllers\Api\TicketApiController::class, 'show'])        ->name('api.tickets.show');
    $r->put('tickets/{id}',           [\App\Controllers\Api\TicketApiController::class, 'update'])      ->name('api.tickets.update');
    $r->delete('tickets/{id}',        [\App\Controllers\Api\TicketApiController::class, 'destroy'])     ->name('api.tickets.destroy');
    $r->post('tickets/{id}/status',   [\App\Controllers\Api\TicketApiController::class, 'changeStatus'])->name('api.tickets.status');
    $r->post('tickets/{id}/assign',   [\App\Controllers\Api\TicketApiController::class, 'assign'])      ->name('api.tickets.assign');
    $r->post('tickets/{id}/reply',    [\App\Controllers\Api\TicketApiController::class, 'reply'])       ->name('api.tickets.reply');
    $r->get('tickets/{id}/history',   [\App\Controllers\Api\TicketApiController::class, 'history'])     ->name('api.tickets.history');

    // Users
    $r->get('users/me',    [\App\Controllers\Api\UserApiController::class, 'me'])     ->name('api.users.me');
    $r->get('users',       [\App\Controllers\Api\UserApiController::class, 'index'])  ->name('api.users.index');
    $r->post('users',      [\App\Controllers\Api\UserApiController::class, 'store'])  ->name('api.users.store');
    $r->get('users/{id}',  [\App\Controllers\Api\UserApiController::class, 'show'])   ->name('api.users.show');
    $r->put('users/{id}',  [\App\Controllers\Api\UserApiController::class, 'update']) ->name('api.users.update');
    $r->delete('users/{id}',[\App\Controllers\Api\UserApiController::class,'destroy'])->name('api.users.destroy');

    // Companies
    $r->get('companies',       [\App\Controllers\Api\CompanyApiController::class, 'index'])  ->name('api.companies.index');
    $r->post('companies',      [\App\Controllers\Api\CompanyApiController::class, 'store'])  ->name('api.companies.store');
    $r->get('companies/{id}',  [\App\Controllers\Api\CompanyApiController::class, 'show'])   ->name('api.companies.show');
    $r->put('companies/{id}',  [\App\Controllers\Api\CompanyApiController::class, 'update']) ->name('api.companies.update');
    $r->delete('companies/{id}',[\App\Controllers\Api\CompanyApiController::class,'destroy'])->name('api.companies.destroy');

    // Stats
    $r->get('stats/summary',     [\App\Controllers\Api\StatsApiController::class, 'summary'])   ->name('api.stats.summary');
    $r->get('stats/trend',       [\App\Controllers\Api\StatsApiController::class, 'trend'])     ->name('api.stats.trend');
    $r->get('stats/by-status',   [\App\Controllers\Api\StatsApiController::class, 'byStatus'])  ->name('api.stats.status');
    $r->get('stats/by-priority', [\App\Controllers\Api\StatsApiController::class, 'byPriority'])->name('api.stats.priority');
    $r->get('stats/employees',   [\App\Controllers\Api\StatsApiController::class, 'employees']) ->name('api.stats.employees');
});

// ── Root redirect ──────────────────────────────────────────────────────────
$router->get('', function () {
    header('Location: ' . url('dashboard'));
    exit;
});
