<?php

use App\Core\Router;

/** @var Router $router */

// ----------------------------------------------------------------
// Auth Routes (no middleware)
// ----------------------------------------------------------------
$router->group(['prefix' => 'auth'], function (Router $router) {

    $router->get('login',  [\App\Controllers\Auth\LoginController::class, 'showLogin'])
           ->name('auth.login');

    $router->post('login', [\App\Controllers\Auth\LoginController::class, 'login'])
           ->name('auth.login.post');

    $router->get('logout', [\App\Controllers\Auth\LogoutController::class, 'logout'])
           ->name('auth.logout');

    $router->get('forgot-password',  [\App\Controllers\Auth\ForgotPasswordController::class, 'show'])
           ->name('auth.forgot');

    $router->post('forgot-password', [\App\Controllers\Auth\ForgotPasswordController::class, 'send'])
           ->name('auth.forgot.post');

    $router->get('reset-password/{token}',  [\App\Controllers\Auth\ResetPasswordController::class, 'show'])
           ->name('auth.reset');

    $router->post('reset-password', [\App\Controllers\Auth\ResetPasswordController::class, 'reset'])
           ->name('auth.reset.post');
});

// ----------------------------------------------------------------
// Root redirect
// ----------------------------------------------------------------
$router->get('/', function () {
    $user = \App\Core\Session::getInstance()->getUser();
    if (! $user) {
        redirect(url('auth/login'));
    }
    redirect(url('dashboard'));
});

// ----------------------------------------------------------------
// Authenticated Routes
// ----------------------------------------------------------------
$router->group(['middleware' => ['auth', 'csrf']], function (Router $router) {

    // Dashboard (role-aware controller)
    $router->get('dashboard', [\App\Controllers\Admin\DashboardController::class, 'index'])
           ->name('dashboard');

    // ── Tickets ──────────────────────────────────────────────
    $router->group(['prefix' => 'tickets'], function (Router $router) {

        $router->get('',         [\App\Controllers\Ticket\TicketController::class, 'index'])
               ->name('tickets.index');

        $router->get('create',   [\App\Controllers\Ticket\TicketController::class, 'create'])
               ->name('tickets.create');

        $router->post('',        [\App\Controllers\Ticket\TicketController::class, 'store'])
               ->name('tickets.store');

        $router->get('{id}',     [\App\Controllers\Ticket\TicketController::class, 'show'])
               ->name('tickets.show');

        $router->get('{id}/edit', [\App\Controllers\Ticket\TicketController::class, 'edit'])
               ->name('tickets.edit');

        $router->post('{id}',    [\App\Controllers\Ticket\TicketController::class, 'update'])
               ->name('tickets.update');

        $router->delete('{id}',  [\App\Controllers\Ticket\TicketController::class, 'destroy'])
               ->name('tickets.destroy');

        // Status change (AJAX)
        $router->post('{id}/status', [\App\Controllers\Ticket\TicketController::class, 'changeStatus'])
               ->name('tickets.status');

        // Assign ticket
        $router->post('{id}/assign', [\App\Controllers\Ticket\TicketController::class, 'assign'])
               ->name('tickets.assign');

        // Conversations
        $router->post('{id}/reply', [\App\Controllers\Ticket\ConversationController::class, 'reply'])
               ->name('tickets.reply');

        $router->delete('conversations/{convId}', [\App\Controllers\Ticket\ConversationController::class, 'destroy'])
               ->name('tickets.conversation.delete');

        // Attachments
        $router->post('{id}/attachments', [\App\Controllers\Ticket\AttachmentController::class, 'upload'])
               ->name('tickets.attachments.upload');

        $router->get('attachments/{attachId}/download', [\App\Controllers\Ticket\AttachmentController::class, 'download'])
               ->name('tickets.attachments.download');

        $router->delete('attachments/{attachId}', [\App\Controllers\Ticket\AttachmentController::class, 'destroy'])
               ->name('tickets.attachments.delete');
    });

    // ── Admin-only Routes ─────────────────────────────────────
    $router->group(['prefix' => 'admin', 'middleware' => 'role:super_admin'], function (Router $router) {

        // Users
        $router->get('users',           [\App\Controllers\Admin\UserController::class, 'index'])   ->name('admin.users.index');
        $router->get('users/create',    [\App\Controllers\Admin\UserController::class, 'create'])  ->name('admin.users.create');
        $router->post('users',          [\App\Controllers\Admin\UserController::class, 'store'])   ->name('admin.users.store');
        $router->get('users/{id}',      [\App\Controllers\Admin\UserController::class, 'edit'])    ->name('admin.users.edit');
        $router->post('users/{id}',     [\App\Controllers\Admin\UserController::class, 'update'])  ->name('admin.users.update');
        $router->delete('users/{id}',   [\App\Controllers\Admin\UserController::class, 'destroy']) ->name('admin.users.destroy');

        // Companies
        $router->get('companies',         [\App\Controllers\Admin\CompanyController::class, 'index'])   ->name('admin.companies.index');
        $router->get('companies/create',  [\App\Controllers\Admin\CompanyController::class, 'create'])  ->name('admin.companies.create');
        $router->post('companies',        [\App\Controllers\Admin\CompanyController::class, 'store'])   ->name('admin.companies.store');
        $router->get('companies/{id}',    [\App\Controllers\Admin\CompanyController::class, 'edit'])    ->name('admin.companies.edit');
        $router->post('companies/{id}',   [\App\Controllers\Admin\CompanyController::class, 'update'])  ->name('admin.companies.update');
        $router->delete('companies/{id}', [\App\Controllers\Admin\CompanyController::class, 'destroy']) ->name('admin.companies.destroy');

        // Reports
        $router->get('reports',            [\App\Controllers\Admin\ReportController::class, 'index'])      ->name('admin.reports');
        $router->get('reports/export/pdf', [\App\Controllers\Admin\ReportController::class, 'exportPdf'])  ->name('admin.reports.pdf');
        $router->get('reports/export/excel',[\App\Controllers\Admin\ReportController::class, 'exportExcel'])->name('admin.reports.excel');

        // Settings
        $router->get('settings',  [\App\Controllers\Admin\SettingsController::class, 'index'])  ->name('admin.settings');
        $router->post('settings', [\App\Controllers\Admin\SettingsController::class, 'update']) ->name('admin.settings.update');

        // Activity Logs
        $router->get('logs', [\App\Controllers\Admin\DashboardController::class, 'logs']) ->name('admin.logs');
    });
});

// ----------------------------------------------------------------
// REST API Routes (Bearer token auth)
// ----------------------------------------------------------------
$router->group(['prefix' => 'api', 'middleware' => 'api.auth'], function (Router $router) {

    // Auth
    $router->post('auth/login',  [\App\Controllers\Api\AuthApiController::class, 'login'])
           ->name('api.auth.login');
    $router->post('auth/logout', [\App\Controllers\Api\AuthApiController::class, 'logout'])
           ->name('api.auth.logout');

    // Users
    $router->get('users',       [\App\Controllers\Api\UserApiController::class, 'index'])   ->name('api.users.index');
    $router->get('users/{id}',  [\App\Controllers\Api\UserApiController::class, 'show'])    ->name('api.users.show');
    $router->post('users',      [\App\Controllers\Api\UserApiController::class, 'store'])   ->name('api.users.store');
    $router->put('users/{id}',  [\App\Controllers\Api\UserApiController::class, 'update'])  ->name('api.users.update');
    $router->delete('users/{id}', [\App\Controllers\Api\UserApiController::class, 'destroy'])->name('api.users.destroy');

    // Companies
    $router->get('companies',         [\App\Controllers\Api\CompanyApiController::class, 'index'])   ->name('api.companies.index');
    $router->get('companies/{id}',    [\App\Controllers\Api\CompanyApiController::class, 'show'])    ->name('api.companies.show');
    $router->post('companies',        [\App\Controllers\Api\CompanyApiController::class, 'store'])   ->name('api.companies.store');
    $router->put('companies/{id}',    [\App\Controllers\Api\CompanyApiController::class, 'update'])  ->name('api.companies.update');
    $router->delete('companies/{id}', [\App\Controllers\Api\CompanyApiController::class, 'destroy']) ->name('api.companies.destroy');

    // Tickets
    $router->get('tickets',           [\App\Controllers\Api\TicketApiController::class, 'index'])   ->name('api.tickets.index');
    $router->get('tickets/{id}',      [\App\Controllers\Api\TicketApiController::class, 'show'])    ->name('api.tickets.show');
    $router->post('tickets',          [\App\Controllers\Api\TicketApiController::class, 'store'])   ->name('api.tickets.store');
    $router->put('tickets/{id}',      [\App\Controllers\Api\TicketApiController::class, 'update'])  ->name('api.tickets.update');
    $router->delete('tickets/{id}',   [\App\Controllers\Api\TicketApiController::class, 'destroy']) ->name('api.tickets.destroy');
});
