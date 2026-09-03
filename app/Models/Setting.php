<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

/**
 * Setting Model
 *
 * Manages key-value portal settings stored in the settings table.
 * Provides caching for performance.
 */
class Setting
{
    private static array $cache = [];

    // Default settings with types and labels
    public const DEFAULTS = [
        // General
        'app_name'              => ['value' => 'Support Portal', 'type' => 'text',     'group' => 'general',       'label' => 'Application Name'],
        'app_tagline'           => ['value' => 'Client Support & Ticket Management', 'type' => 'text', 'group' => 'general', 'label' => 'Tagline'],
        'support_email'         => ['value' => 'support@example.com', 'type' => 'email', 'group' => 'general',    'label' => 'Support Email'],
        'tickets_per_page'      => ['value' => '20',  'type' => 'number',   'group' => 'general',       'label' => 'Tickets Per Page'],
        'max_attachment_size'   => ['value' => '10',  'type' => 'number',   'group' => 'general',       'label' => 'Max Attachment Size (MB)'],
        'allowed_file_types'    => ['value' => 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt', 'type' => 'text', 'group' => 'general', 'label' => 'Allowed File Types'],
        'maintenance_mode'      => ['value' => '0',   'type' => 'boolean',  'group' => 'general',       'label' => 'Maintenance Mode'],

        // Notifications
        'email_notifications'      => ['value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Enable Email Notifications'],
        'notify_new_ticket'        => ['value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on New Ticket'],
        'notify_ticket_assigned'   => ['value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on Assignment'],
        'notify_ticket_resolved'   => ['value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on Resolution'],
        'notify_ticket_reply'      => ['value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on Reply'],

        // Ticket
        'auto_close_days'       => ['value' => '7',   'type' => 'number',   'group' => 'tickets',       'label' => 'Auto-Close After (days, 0 = off)'],
        'require_category'      => ['value' => '0',   'type' => 'boolean',  'group' => 'tickets',       'label' => 'Require Category on Ticket'],
        'allow_client_close'    => ['value' => '1',   'type' => 'boolean',  'group' => 'tickets',       'label' => 'Allow Clients to Close Tickets'],
        'default_priority'      => ['value' => 'medium', 'type' => 'select', 'group' => 'tickets',      'label' => 'Default Priority',
                                    'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']],

        // Security
        'max_login_attempts'    => ['value' => '5',   'type' => 'number',   'group' => 'security',      'label' => 'Max Login Attempts'],
        'lockout_duration'      => ['value' => '15',  'type' => 'number',   'group' => 'security',      'label' => 'Lockout Duration (minutes)'],
        'session_lifetime'      => ['value' => '120', 'type' => 'number',   'group' => 'security',      'label' => 'Session Lifetime (minutes)'],
        'require_strong_password'=> ['value'=> '1',   'type' => 'boolean',  'group' => 'security',      'label' => 'Require Strong Password'],
        'log_activity'          => ['value' => '1',   'type' => 'boolean',  'group' => 'security',      'label' => 'Enable Activity Logging'],
    ];

    // ── Get/Set ───────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $db  = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT value FROM settings WHERE key_name = ?",
            [$key]
        );

        $value = $row ? $row['value'] : ($default ?? self::DEFAULTS[$key]['value'] ?? null);
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $db  = Database::getInstance();
        $existing = $db->fetchOne(
            "SELECT id FROM settings WHERE key_name = ?",
            [$key]
        );

        if ($existing) {
            $db->update('settings', ['value' => (string)$value, 'updated_at' => date('Y-m-d H:i:s')], ['key_name' => $key]);
        } else {
            $db->insert('settings', [
                'key_name'   => $key,
                'value'      => (string)$value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        self::$cache[$key] = (string)$value;
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                self::set($key, $value);
            }
        }
    }

    public static function getGroup(string $group): array
    {
        $db   = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT key_name, value FROM settings"
        );

        $stored = array_column($rows, 'value', 'key_name');
        $result = [];

        foreach (self::DEFAULTS as $key => $def) {
            if ($def['group'] === $group) {
                $result[$key] = array_merge($def, [
                    'key'   => $key,
                    'value' => $stored[$key] ?? $def['value'],
                ]);
            }
        }

        return $result;
    }

    public static function getAll(): array
    {
        $db   = Database::getInstance();
        $rows = $db->fetchAll("SELECT key_name, value FROM settings");
        $stored = array_column($rows, 'value', 'key_name');

        $result = [];
        foreach (self::DEFAULTS as $key => $def) {
            $result[$key] = $stored[$key] ?? $def['value'];
        }
        return $result;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if ($val === null) return $default;
        return (bool)(int)$val;
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int)(self::get($key) ?? $default);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
