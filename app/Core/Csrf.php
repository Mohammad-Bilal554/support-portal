<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF Protection
 *
 * Double-submit cookie + session token pattern.
 * Token is regenerated after each successful verification.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME  = '_csrf_token';
    private const LIFETIME    = 3600;  // seconds

    // ----------------------------------------------------------------
    // Token management
    // ----------------------------------------------------------------

    /** Get existing token or generate a new one */
    public static function getToken(): string
    {
        $session = Session::getInstance();
        $token   = $session->get(static::SESSION_KEY);

        if (! $token || static::isExpired($token)) {
            $token = static::generateToken();
            $session->set(static::SESSION_KEY, $token);
        }

        return $token['value'];
    }

    private static function generateToken(): array
    {
        return [
            'value'      => bin2hex(random_bytes(32)),
            'created_at' => time(),
        ];
    }

    private static function isExpired(array $token): bool
    {
        $lifetime = (int) env('CSRF_TOKEN_LIFETIME', static::LIFETIME);
        return (time() - ($token['created_at'] ?? 0)) > $lifetime;
    }

    // ----------------------------------------------------------------
    // Verification
    // ----------------------------------------------------------------

    public static function verify(Request $request): bool
    {
        // Skip safe methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        // Allow API routes (they use Bearer tokens)
        if (str_contains($request->uri(), '/api/')) {
            return true;
        }

        $session  = Session::getInstance();
        $stored   = $session->get(static::SESSION_KEY);

        if (! $stored) {
            return false;
        }

        // Check header first (AJAX), then form field
        $submitted = $request->header('X-CSRF-Token')
            ?? $request->input(static::FIELD_NAME);

        if (! $submitted) {
            return false;
        }

        return hash_equals($stored['value'], $submitted);
    }

    /** Regenerate token after verification (CSRF token rotation) */
    public static function regenerate(): void
    {
        Session::getInstance()->set(
            static::SESSION_KEY,
            static::generateToken()
        );
    }

    // ----------------------------------------------------------------
    // HTML helpers
    // ----------------------------------------------------------------

    public static function field(): string
    {
        $token = static::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            static::FIELD_NAME,
            htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }

    public static function metaTag(): string
    {
        $token = static::getToken();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }
}
