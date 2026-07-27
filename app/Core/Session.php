<?php
declare(strict_types=1);
namespace App\Core;

class Session {
    private static ?Session $instance = null;
    public static function getInstance(): static {
        if (!static::$instance) static::$instance = new static();
        return static::$instance;
    }
    public function set(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public function has(string $key): bool { return isset($_SESSION[$key]); }
    public function remove(string $key): void { unset($_SESSION[$key]); }
    public function setFlash(string $key, mixed $value): void { $_SESSION['_flash'][$key] = $value; }
    public function getFlash(string $key, mixed $default = null): mixed {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    public function hasFlash(string $key): bool { return isset($_SESSION['_flash'][$key]); }
    public function flash(string $key, mixed $value): void { $this->setFlash($key, $value); }
    public function success(string $m): void { $this->setFlash('success', $m); }
    public function error(string $m): void   { $this->setFlash('error', $m); }
    public function warning(string $m): void { $this->setFlash('warning', $m); }
    public function info(string $m): void    { $this->setFlash('info', $m); }
    public function setUser(array $user): void { $this->set('auth_user', $user); }
    public function getUser(): ?array { return $this->get('auth_user'); }
    public function isLoggedIn(): bool { return $this->has('auth_user'); }
    public function getUserId(): ?int { $u=$this->getUser(); return $u ? (int)$u['id'] : null; }
    public function getUserRole(): ?string { return $this->getUser()['role'] ?? null; }
    public function regenerate(bool $deleteOld = true): void {
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id($deleteOld);
    }
    public function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
