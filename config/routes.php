<?php
use App\Core\Router;
/** @var Router $router */

// Auth routes
$router->group(['prefix'=>'auth'], function(Router $r) {
    $r->get('login',            [\App\Controllers\Auth\LoginController::class,'showLogin'])       ->name('auth.login');
    $r->post('login',           [\App\Controllers\Auth\LoginController::class,'login'])           ->name('auth.login.post');
    $r->get('logout',           [\App\Controllers\Auth\LogoutController::class,'logout'])         ->name('auth.logout');
    $r->get('forgot-password',  [\App\Controllers\Auth\ForgotPasswordController::class,'show'])   ->name('auth.forgot');
    $r->post('forgot-password', [\App\Controllers\Auth\ForgotPasswordController::class,'send'])   ->name('auth.forgot.post');
    $r->get('reset-password/{token}', [\App\Controllers\Auth\ResetPasswordController::class,'show'])  ->name('auth.reset');
    $r->post('reset-password',        [\App\Controllers\Auth\ResetPasswordController::class,'reset']) ->name('auth.reset.post');
});

// Root redirect
$router->get('/', function() {
    \App\Core\Session::getInstance()->isLoggedIn() ? redirect(url('dashboard')) : redirect(url('auth/login'));
});

// Authenticated routes
$router->group(['middleware'=>['auth','csrf']], function(Router $r) {
    $r->get('dashboard', [\App\Controllers\Admin\DashboardController::class,'index'])->name('dashboard');

    // Tickets
    $r->group(['prefix'=>'tickets'], function(Router $r) {
        $r->get('',              [\App\Controllers\Ticket\TicketController::class,'index'])        ->name('tickets.index');
        $r->get('create',        [\App\Controllers\Ticket\TicketController::class,'create'])       ->name('tickets.create');
        $r->post('',             [\App\Controllers\Ticket\TicketController::class,'store'])        ->name('tickets.store');
        $r->get('{id}',          [\App\Controllers\Ticket\TicketController::class,'show'])         ->name('tickets.show');
        $r->get('{id}/edit',     [\App\Controllers\Ticket\TicketController::class,'edit'])         ->name('tickets.edit');
        $r->post('{id}',         [\App\Controllers\Ticket\TicketController::class,'update'])       ->name('tickets.update');
        $r->delete('{id}',       [\App\Controllers\Ticket\TicketController::class,'destroy'])      ->name('tickets.destroy');
        $r->post('{id}/status',  [\App\Controllers\Ticket\TicketController::class,'changeStatus']) ->name('tickets.status');
        $r->post('{id}/assign',  [\App\Controllers\Ticket\TicketController::class,'assign'])       ->name('tickets.assign');
        $r->post('{id}/reply',   [\App\Controllers\Ticket\ConversationController::class,'reply'])  ->name('tickets.reply');
        $r->delete('conversations/{cid}', [\App\Controllers\Ticket\ConversationController::class,'destroy'])->name('tickets.conversation.delete');
        $r->post('{id}/attachments',        [\App\Controllers\Ticket\AttachmentController::class,'upload'])  ->name('tickets.attachments.upload');
        $r->get('attachments/{aid}/download',[\App\Controllers\Ticket\AttachmentController::class,'download'])->name('tickets.attachments.download');
        $r->delete('attachments/{aid}',     [\App\Controllers\Ticket\AttachmentController::class,'destroy']) ->name('tickets.attachments.delete');
    });

    // Admin only
    $r->group(['prefix'=>'admin','middleware'=>'role:super_admin'], function(Router $r) {
        $r->get('users',            [\App\Controllers\Admin\UserController::class,'index'])    ->name('admin.users.index');
        $r->get('users/create',     [\App\Controllers\Admin\UserController::class,'create'])   ->name('admin.users.create');
        $r->post('users',           [\App\Controllers\Admin\UserController::class,'store'])    ->name('admin.users.store');
        $r->get('users/{id}',       [\App\Controllers\Admin\UserController::class,'edit'])     ->name('admin.users.edit');
        $r->post('users/{id}',      [\App\Controllers\Admin\UserController::class,'update'])   ->name('admin.users.update');
        $r->delete('users/{id}',    [\App\Controllers\Admin\UserController::class,'destroy'])  ->name('admin.users.destroy');
        $r->get('companies',        [\App\Controllers\Admin\CompanyController::class,'index']) ->name('admin.companies.index');
        $r->get('companies/create', [\App\Controllers\Admin\CompanyController::class,'create'])->name('admin.companies.create');
        $r->post('companies',       [\App\Controllers\Admin\CompanyController::class,'store']) ->name('admin.companies.store');
        $r->get('companies/{id}',   [\App\Controllers\Admin\CompanyController::class,'edit'])  ->name('admin.companies.edit');
        $r->post('companies/{id}',  [\App\Controllers\Admin\CompanyController::class,'update'])->name('admin.companies.update');
        $r->delete('companies/{id}',[\App\Controllers\Admin\CompanyController::class,'destroy'])->name('admin.companies.destroy');
        $r->get('reports',               [\App\Controllers\Admin\ReportController::class,'index'])       ->name('admin.reports');
        $r->get('reports/export/pdf',    [\App\Controllers\Admin\ReportController::class,'exportPdf'])   ->name('admin.reports.pdf');
        $r->get('reports/export/excel',  [\App\Controllers\Admin\ReportController::class,'exportExcel']) ->name('admin.reports.excel');
        $r->get('settings',  [\App\Controllers\Admin\SettingsController::class,'index'])  ->name('admin.settings');
        $r->post('settings', [\App\Controllers\Admin\SettingsController::class,'update']) ->name('admin.settings.update');
        $r->get('logs',      [\App\Controllers\Admin\DashboardController::class,'logs'])  ->name('admin.logs');
    });
});

// API routes
$router->group(['prefix'=>'api','middleware'=>'api.auth'], function(Router $r) {
    $r->post('auth/login',      [\App\Controllers\Api\AuthApiController::class,'login'])    ->name('api.auth.login');
    $r->post('auth/logout',     [\App\Controllers\Api\AuthApiController::class,'logout'])   ->name('api.auth.logout');
    $r->get('users',            [\App\Controllers\Api\UserApiController::class,'index'])    ->name('api.users.index');
    $r->get('users/{id}',       [\App\Controllers\Api\UserApiController::class,'show'])     ->name('api.users.show');
    $r->post('users',           [\App\Controllers\Api\UserApiController::class,'store'])    ->name('api.users.store');
    $r->put('users/{id}',       [\App\Controllers\Api\UserApiController::class,'update'])   ->name('api.users.update');
    $r->delete('users/{id}',    [\App\Controllers\Api\UserApiController::class,'destroy'])  ->name('api.users.destroy');
    $r->get('companies',        [\App\Controllers\Api\CompanyApiController::class,'index']) ->name('api.companies.index');
    $r->get('companies/{id}',   [\App\Controllers\Api\CompanyApiController::class,'show'])  ->name('api.companies.show');
    $r->post('companies',       [\App\Controllers\Api\CompanyApiController::class,'store']) ->name('api.companies.store');
    $r->put('companies/{id}',   [\App\Controllers\Api\CompanyApiController::class,'update'])->name('api.companies.update');
    $r->delete('companies/{id}',[\App\Controllers\Api\CompanyApiController::class,'destroy'])->name('api.companies.destroy');
    $r->get('tickets',          [\App\Controllers\Api\TicketApiController::class,'index'])  ->name('api.tickets.index');
    $r->get('tickets/{id}',     [\App\Controllers\Api\TicketApiController::class,'show'])   ->name('api.tickets.show');
    $r->post('tickets',         [\App\Controllers\Api\TicketApiController::class,'store'])  ->name('api.tickets.store');
    $r->put('tickets/{id}',     [\App\Controllers\Api\TicketApiController::class,'update']) ->name('api.tickets.update');
    $r->delete('tickets/{id}',  [\App\Controllers\Api\TicketApiController::class,'destroy'])->name('api.tickets.destroy');
});
