<?php

/**
 * Role-based permissions map.
 * Used by the RoleMiddleware and in-controller authorization.
 */
return [
    'super_admin' => [
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
        'tickets.view_all', 'tickets.create', 'tickets.edit', 'tickets.delete',
        'tickets.assign', 'tickets.change_status', 'tickets.internal_note',
        'reports.view', 'reports.export',
        'settings.view', 'settings.edit',
        'logs.view',
        'api.access',
    ],
    'employee' => [
        'tickets.view_assigned', 'tickets.create', 'tickets.edit',
        'tickets.change_status', 'tickets.internal_note',
        'reports.view',
    ],
    'client' => [
        'tickets.view_own', 'tickets.create',
    ],
];
