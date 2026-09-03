<?php
/**
 * API Documentation Page
 */
$title = 'REST API Documentation';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">REST API Documentation</h1>
        <p class="page-subtitle">Base URL: <code><?= rtrim(env('APP_URL','http://localhost/support-portal/public'), '/') ?>/api/v1</code></p>
    </div>
    <span class="badge bg-success" style="font-size:.85rem;padding:.5rem 1rem;">v1</span>
</div>

<!-- Auth Section -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Authentication</span>
    </div>
    <div class="card-body">
        <p style="font-size:.9rem;">All API endpoints (except token generation) require a <strong>Bearer token</strong> in the <code>Authorization</code> header.</p>

        <div style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:1rem;font-family:monospace;font-size:.82rem;line-height:2;">
            <span style="color:#94a3b8;"># Step 1: Generate a token</span><br>
            POST /api/v1/auth/token<br>
            Content-Type: application/json<br><br>
            {<br>
            &nbsp;&nbsp;"email": "admin@support-portal.com",<br>
            &nbsp;&nbsp;"password": "Admin@12345",<br>
            &nbsp;&nbsp;"name": "My App Token",<br>
            &nbsp;&nbsp;"expires_in": 30<br>
            }<br><br>
            <span style="color:#94a3b8;"># Step 2: Use in subsequent requests</span><br>
            Authorization: Bearer &lt;your_token&gt;
        </div>

        <div class="alert alert-warning mt-3 mb-0" style="font-size:.83rem;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Tokens are only shown <strong>once</strong> at creation. Store them securely.
            Rate limit: <strong>60 requests/minute</strong> per token.
        </div>
    </div>
</div>

<!-- Endpoints -->
<?php
$sections = [
    [
        'title' => 'Auth Token Management',
        'icon'  => 'bi-key-fill',
        'color' => 'text-warning',
        'endpoints' => [
            ['POST', '/api/v1/auth/token',  'Generate new API token',  false, '{"email":"...","password":"...","name":"My Token","expires_in":30}'],
            ['POST', '/api/v1/auth/revoke', 'Revoke current token',    true,  null],
            ['GET',  '/api/v1/auth/tokens', 'List all your tokens',    true,  null],
        ],
    ],
    [
        'title' => 'Tickets',
        'icon'  => 'bi-ticket-perforated-fill',
        'color' => 'text-primary',
        'endpoints' => [
            ['GET',    '/api/v1/tickets',                  'List tickets (paginated, filtered)',     true, null],
            ['POST',   '/api/v1/tickets',                  'Create new ticket',                     true, '{"subject":"...","description":"...","priority":"high","category_id":1}'],
            ['GET',    '/api/v1/tickets/{id}',             'Get ticket with conversations',          true, null],
            ['PUT',    '/api/v1/tickets/{id}',             'Update ticket (staff only)',             true, '{"subject":"...","description":"...","priority":"medium"}'],
            ['DELETE', '/api/v1/tickets/{id}',             'Delete ticket (admin only)',             true, null],
            ['POST',   '/api/v1/tickets/{id}/status',      'Change ticket status',                  true, '{"status":"in_progress","note":"Working on it"}'],
            ['POST',   '/api/v1/tickets/{id}/assign',      'Assign ticket to employee',             true, '{"assigned_to":2}'],
            ['POST',   '/api/v1/tickets/{id}/reply',       'Add reply or internal note',            true, '{"message":"...","is_internal":false}'],
            ['GET',    '/api/v1/tickets/{id}/history',     'Get status history',                    true, null],
        ],
    ],
    [
        'title' => 'Users',
        'icon'  => 'bi-people-fill',
        'color' => 'text-success',
        'endpoints' => [
            ['GET',    '/api/v1/users/me',   'Get authenticated user profile',  true, null],
            ['GET',    '/api/v1/users',       'List users (admin only)',          true, null],
            ['POST',   '/api/v1/users',       'Create user (admin only)',         true, '{"first_name":"...","last_name":"...","email":"...","password":"...","role":"employee"}'],
            ['GET',    '/api/v1/users/{id}',  'Get single user (admin only)',    true, null],
            ['PUT',    '/api/v1/users/{id}',  'Update user (admin only)',         true, null],
            ['DELETE', '/api/v1/users/{id}',  'Deactivate user (admin only)',    true, null],
        ],
    ],
    [
        'title' => 'Companies',
        'icon'  => 'bi-building-fill',
        'color' => 'text-info',
        'endpoints' => [
            ['GET',    '/api/v1/companies',      'List companies (admin only)',    true, null],
            ['POST',   '/api/v1/companies',      'Create company (admin only)',   true, '{"name":"Acme Corp","email":"info@acme.com"}'],
            ['GET',    '/api/v1/companies/{id}', 'Get company with stats',        true, null],
            ['PUT',    '/api/v1/companies/{id}', 'Update company (admin only)',   true, null],
            ['DELETE', '/api/v1/companies/{id}', 'Delete company (admin only)',   true, null],
        ],
    ],
    [
        'title' => 'Statistics',
        'icon'  => 'bi-graph-up',
        'color' => 'text-danger',
        'endpoints' => [
            ['GET', '/api/v1/stats/summary',     'Dashboard summary KPIs (staff)',      true, null],
            ['GET', '/api/v1/stats/trend',       'Daily ticket trend data (staff)',     true, null],
            ['GET', '/api/v1/stats/by-status',   'Tickets by status (staff)',           true, null],
            ['GET', '/api/v1/stats/by-priority', 'Tickets by priority (staff)',         true, null],
            ['GET', '/api/v1/stats/employees',   'Employee performance (admin only)',   true, null],
        ],
    ],
];

$methodColors = [
    'GET'    => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
    'POST'   => ['bg'=>'#dcfce7','color'=>'#166534'],
    'PUT'    => ['bg'=>'#fffbeb','color'=>'#92400e'],
    'PATCH'  => ['bg'=>'#f3e8ff','color'=>'#6d28d9'],
    'DELETE' => ['bg'=>'#fee2e2','color'=>'#991b1b'],
];

foreach ($sections as $section):
?>
<div class="card mb-4">
    <div class="card-header">
        <span>
            <i class="bi <?= $section['icon'] ?> me-2 <?= $section['color'] ?>"></i>
            <?= $section['title'] ?>
        </span>
    </div>
    <div class="card-body p-0">
        <?php foreach ($section['endpoints'] as $i => [$method, $path, $desc, $auth, $body]): ?>
        <?php
            $mc = $methodColors[$method] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
            $border = $i < count($section['endpoints']) - 1 ? 'border-bottom' : '';
        ?>
        <div class="<?= $border ?>" style="padding:.9rem 1.25rem;">
            <div class="d-flex align-items-start gap-3 flex-wrap">
                <span style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>;font-family:monospace;font-size:.75rem;font-weight:700;padding:.25rem .6rem;border-radius:6px;min-width:56px;text-align:center;flex-shrink:0;">
                    <?= $method ?>
                </span>
                <code style="font-size:.8rem;color:var(--text-primary);flex:1;min-width:200px;">
                    <?= htmlspecialchars($path) ?>
                </code>
                <span style="font-size:.82rem;color:var(--text-secondary);flex:2;">
                    <?= htmlspecialchars($desc) ?>
                    <?php if ($auth): ?>
                    <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.65rem;font-weight:500;margin-left:6px;">
                        <i class="bi bi-lock-fill me-1"></i>Auth required
                    </span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($body): ?>
            <div style="margin-top:.6rem;background:#f8fafc;border-radius:6px;padding:.5rem .75rem;font-family:monospace;font-size:.75rem;color:#374151;">
                <?= htmlspecialchars($body) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Query params -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class="bi bi-funnel-fill me-2 text-primary"></i>Query Parameters (Tickets)</span>
    </div>
    <div class="card-body p-0">
        <?php foreach ([
            ['search',      'string',  'Full-text search (subject, ticket #, description)'],
            ['status',      'string',  'Filter by status: open, assigned, in_progress, waiting_for_client, resolved, closed'],
            ['priority',    'string',  'Filter by priority: low, medium, high, critical'],
            ['category_id', 'integer', 'Filter by category ID'],
            ['company_id',  'integer', 'Filter by company ID'],
            ['assigned_to', 'integer', 'Filter by assignee user ID'],
            ['page',        'integer', 'Page number (default: 1)'],
            ['per_page',    'integer', 'Results per page (max: 50, default: 20)'],
        ] as [$param, $type, $desc]): ?>
        <div class="d-flex align-items-center gap-3 px-4 py-2 border-bottom" style="font-size:.82rem;">
            <code style="width:110px;flex-shrink:0;"><?= $param ?></code>
            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:.7rem;width:55px;text-align:center;"><?= $type ?></span>
            <span style="color:var(--text-secondary);"><?= $desc ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Response format -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-braces me-2 text-primary"></i>Response Format</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <p class="fw-semibold mb-2" style="font-size:.85rem;">Success</p>
                <div style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:.85rem;font-family:monospace;font-size:.78rem;line-height:1.8;">
                    {<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"success"</span>: true,<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"message"</span>: "OK",<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"data"</span>: { ... }<br>
                    }
                </div>
            </div>
            <div class="col-md-4">
                <p class="fw-semibold mb-2" style="font-size:.85rem;">Paginated List</p>
                <div style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:.85rem;font-family:monospace;font-size:.78rem;line-height:1.8;">
                    {<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"success"</span>: true,<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"data"</span>: [ ... ],<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"total"</span>: 142,<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"current_page"</span>: 1,<br>
                    &nbsp;&nbsp;<span style="color:#7dd3fc;">"last_page"</span>: 8<br>
                    }
                </div>
            </div>
            <div class="col-md-4">
                <p class="fw-semibold mb-2" style="font-size:.85rem;">Error</p>
                <div style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:.85rem;font-family:monospace;font-size:.78rem;line-height:1.8;">
                    {<br>
                    &nbsp;&nbsp;<span style="color:#fca5a5;">"success"</span>: false,<br>
                    &nbsp;&nbsp;<span style="color:#fca5a5;">"error"</span>: "Not Found",<br>
                    &nbsp;&nbsp;<span style="color:#fca5a5;">"message"</span>: "..."<br>
                    }
                </div>
            </div>
        </div>

        <!-- HTTP Status codes -->
        <div class="mt-3">
            <p class="fw-semibold mb-2" style="font-size:.85rem;">HTTP Status Codes</p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ([
                    ['200','OK','bg-success'],
                    ['201','Created','bg-success'],
                    ['400','Bad Request','bg-warning'],
                    ['401','Unauthorized','bg-danger'],
                    ['403','Forbidden','bg-danger'],
                    ['404','Not Found','bg-secondary'],
                    ['422','Validation Error','bg-warning'],
                    ['429','Rate Limited','bg-danger'],
                ] as [$code, $label, $cls]): ?>
                <span class="badge <?= $cls ?>" style="font-size:.75rem;padding:.35rem .65rem;">
                    <?= $code ?> <?= $label ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
