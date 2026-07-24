<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request
 *
 * Wraps the HTTP request: superglobals, headers,
 * input sanitization, file uploads, JSON body.
 */
class Request
{
    private array $queryParams;
    private array $bodyParams;
    private array $files;
    private array $serverParams;
    private array $headers;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->queryParams  = $_GET    ?? [];
        $this->bodyParams   = $_POST   ?? [];
        $this->files        = $_FILES  ?? [];
        $this->serverParams = $_SERVER ?? [];
        $this->headers      = $this->parseHeaders();
        $this->parseJsonBody();
    }

    // ----------------------------------------------------------------
    // Method & URI
    // ----------------------------------------------------------------

    public function method(): string
    {
        return strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool    { return $this->method() === 'GET'; }
    public function isPost(): bool   { return $this->method() === 'POST'; }
    public function isPut(): bool    { return $this->method() === 'PUT'; }
    public function isPatch(): bool  { return $this->method() === 'PATCH'; }
    public function isDelete(): bool { return $this->method() === 'DELETE'; }
    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') ?? '') === 'XMLHttpRequest';
    }
    public function isJson(): bool
    {
        $ct = $this->header('Content-Type') ?? '';
        return str_contains($ct, 'application/json');
    }

    /** Full URI including query string */
    public function uri(): string
    {
        return $this->serverParams['REQUEST_URI'] ?? '/';
    }

    /** URI path only (no query string) */
    public function pathInfo(): string
    {
        $uri = parse_url($this->uri(), PHP_URL_PATH) ?? '/';

        // Normalize and strip the script sub-directory from the path
        $scriptDir = str_replace('\\', '/', dirname($this->serverParams['SCRIPT_NAME'] ?? ''));

        if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
            if (str_starts_with($uri, $scriptDir)) {
                $uri = substr($uri, strlen($scriptDir));
            } elseif (str_ends_with($scriptDir, '/public')) {
                $baseDir = substr($scriptDir, 0, -7);
                if ($baseDir !== '' && str_starts_with($uri, $baseDir)) {
                    $uri = substr($uri, strlen($baseDir));
                }
            }
        }

        return '/' . ltrim($uri, '/');
    }

    public function fullUrl(): string
    {
        $scheme = isset($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] !== 'off'
            ? 'https' : 'http';
        $host = $this->serverParams['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $this->uri();
    }

    // ----------------------------------------------------------------
    // Input retrieval
    // ----------------------------------------------------------------

    /**
     * Get input value from POST, GET, or JSON body.
     * Returns $default if key is missing.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        // JSON body takes precedence for JSON requests
        if ($this->jsonBody !== null && array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }

        return $this->bodyParams[$key]
            ?? $this->queryParams[$key]
            ?? $default;
    }

    /** Get all input (merged POST + GET + JSON) */
    public function all(): array
    {
        $base = array_merge($this->queryParams, $this->bodyParams);

        if ($this->jsonBody !== null) {
            $base = array_merge($base, $this->jsonBody);
        }

        return $base;
    }

    /** Get only specified keys */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /** Get all except specified keys */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /** Check if key exists in input */
    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }

    /** Check if key exists and is not empty */
    public function filled(string $key): bool
    {
        $val = $this->input($key);
        return $val !== null && $val !== '';
    }

    /** Query string param */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    // ----------------------------------------------------------------
    // Sanitized input
    // ----------------------------------------------------------------

    /** Returns HTML-entity-encoded string */
    public function safe(string $key, mixed $default = null): ?string
    {
        $val = $this->input($key, $default);
        return $val !== null ? htmlspecialchars((string) $val, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
    }

    /** Returns integer value */
    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->input($key, $default);
    }

    /** Returns boolean value */
    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    // ----------------------------------------------------------------
    // Headers
    // ----------------------------------------------------------------

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization') ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    private function parseHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
            return $headers;
        }

        // Fallback: parse $_SERVER
        foreach ($this->serverParams as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    // ----------------------------------------------------------------
    // JSON
    // ----------------------------------------------------------------

    private function parseJsonBody(): void
    {
        if ($this->isJson() && in_array($this->method(), ['POST', 'PUT', 'PATCH'])) {
            $body = file_get_contents('php://input');
            if ($body) {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->jsonBody = $decoded;
                }
            }
        }
    }

    public function json(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->jsonBody;
        }
        return $this->jsonBody[$key] ?? $default;
    }

    // ----------------------------------------------------------------
    // File uploads
    // ----------------------------------------------------------------

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key])
            && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    // ----------------------------------------------------------------
    // IP / Client
    // ----------------------------------------------------------------

    public function ip(): string
    {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            if (! empty($this->serverParams[$key])) {
                $ip = trim(explode(',', $this->serverParams[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->serverParams['HTTP_USER_AGENT'] ?? '';
    }

    // ----------------------------------------------------------------
    // Validation shortcut (delegates to Validator)
    // ----------------------------------------------------------------

    public function validate(array $rules): array
    {
        $validator = new Validator($this->all(), $rules);

        if ($validator->fails()) {
            if ($this->isAjax() || $this->isJson()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ]);
                exit;
            }

            // Store errors in session and redirect back
            Session::getInstance()->setFlash('errors', $validator->errors());
            Session::getInstance()->setFlash('old', $this->all());
            redirect_back();
            exit;
        }

        return $validator->validated();
    }
}
