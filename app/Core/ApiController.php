<?php
declare(strict_types=1);
namespace App\Core;

/**
 * ApiController
 *
 * Base class for all REST API controllers.
 * Provides JSON response helpers, pagination formatting,
 * and role-based authorization shortcuts.
 */
abstract class ApiController
{
    protected Request  $request;
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
    }

    // ── Response helpers ──────────────────────────────────────────

    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): string
    {
        http_response_code($code);
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function paginated(array $paginator, string $message = 'OK'): string
    {
        http_response_code(200);
        return json_encode([
            'success'     => true,
            'message'     => $message,
            'data'        => $paginator['data']        ?? [],
            'total'       => $paginator['total']       ?? 0,
            'per_page'    => $paginator['per_page']    ?? 20,
            'current_page'=> $paginator['current_page']?? 1,
            'last_page'   => $paginator['last_page']   ?? 1,
            'from'        => $paginator['from']        ?? 0,
            'to'          => $paginator['to']          ?? 0,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function error(string $message, int $code = 400, array $errors = []): string
    {
        http_response_code($code);
        $response = ['success' => false, 'error' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function notFound(string $message = 'Resource not found.'): string
    {
        return $this->error($message, 404);
    }

    protected function forbidden(string $message = 'Access denied.'): string
    {
        return $this->error($message, 403);
    }

    protected function validationError(array $errors): string
    {
        return $this->error('Validation failed.', 422, $errors);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully.'): string
    {
        return $this->success($data, $message, 201);
    }

    // ── Auth helpers ──────────────────────────────────────────────

    protected function user(): array
    {
        return $this->request->getUser() ?? [];
    }

    protected function userId(): int
    {
        return (int)($this->user()['id'] ?? 0);
    }

    protected function userRole(): string
    {
        return $this->user()['role'] ?? 'client';
    }

    protected function isAdmin(): bool
    {
        return $this->userRole() === 'super_admin';
    }

    protected function isEmployee(): bool
    {
        return in_array($this->userRole(), ['super_admin', 'employee']);
    }

    protected function isClient(): bool
    {
        return $this->userRole() === 'client';
    }

    // ── Input helpers ─────────────────────────────────────────────

    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->request->input($key, $default);
    }

    protected function integer(string $key, int $default = 0): int
    {
        return (int)$this->request->input($key, $default);
    }

    protected function query(string $key, mixed $default = null): mixed
    {
        return $this->request->query($key, $default);
    }

    protected function page(): int
    {
        return max(1, (int)$this->request->query('page', 1));
    }

    protected function perPage(int $max = 100): int
    {
        return min($max, max(1, (int)$this->request->query('per_page', 20)));
    }

    // ── Validation ────────────────────────────────────────────────

    protected function validate(array $data, array $rules): array
    {
        $validator = new Validator($data, $rules);
        if ($validator->fails()) {
            // Flatten errors to single messages
            $flat = [];
            foreach ($validator->errors() as $field => $msgs) {
                $flat[$field] = is_array($msgs) ? $msgs[0] : $msgs;
            }
            echo $this->validationError($flat);
            exit;
        }
        return $data;
    }
}
