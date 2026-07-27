<?php
declare(strict_types=1);
namespace App\Core;

class Csrf {
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME  = '_csrf_token';

    public static function getToken(): string {
        $session = Session::getInstance();
        $token   = $session->get(static::SESSION_KEY);
        if (!$token || (time() - ($token['created_at']??0)) > (int)env('CSRF_TOKEN_LIFETIME',3600)) {
            $token = ['value'=>bin2hex(random_bytes(32)),'created_at'=>time()];
            $session->set(static::SESSION_KEY, $token);
        }
        return $token['value'];
    }
    public static function verify(Request $request): bool {
        if (in_array($request->method(), ['GET','HEAD','OPTIONS'])) return true;
        if (str_contains($request->uri(), '/api/')) return true;
        $stored = Session::getInstance()->get(static::SESSION_KEY);
        if (!$stored) return false;
        $submitted = $request->header('X-CSRF-Token') ?? $request->input(static::FIELD_NAME);
        if (!$submitted) return false;
        return hash_equals($stored['value'], $submitted);
    }
    public static function field(): string {
        return '<input type="hidden" name="'.static::FIELD_NAME.'" value="'.htmlspecialchars(static::getToken(),ENT_QUOTES,'UTF-8').'">';
    }
    public static function metaTag(): string {
        return '<meta name="csrf-token" content="'.htmlspecialchars(static::getToken(),ENT_QUOTES,'UTF-8').'">';
    }
}
