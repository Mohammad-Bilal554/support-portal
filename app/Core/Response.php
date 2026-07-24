<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response
 *
 * Fluent HTTP response builder with helpers
 * for HTML, JSON, redirects, and file downloads.
 */
class Response
{
    private int    $statusCode = 200;
    private array  $headers    = [];
    private string $body       = '';

    // ----------------------------------------------------------------
    // Status
    // ----------------------------------------------------------------

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // ----------------------------------------------------------------
    // Headers
    // ----------------------------------------------------------------

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    // ----------------------------------------------------------------
    // Body
    // ----------------------------------------------------------------

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    // ----------------------------------------------------------------
    // Response types
    // ----------------------------------------------------------------

    public function html(string $content, int $status = 200): static
    {
        $this->statusCode = $status;
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->body = $content;
        return $this;
    }

    public function json(mixed $data, int $status = 200): static
    {
        $this->statusCode = $status;
        $this->setHeader('Content-Type', 'application/json');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

    public function jsonSuccess(mixed $data = null, string $message = 'Success', int $status = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status)->send();
    }

    public function jsonError(string $message = 'Error', int $status = 400, array $errors = []): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        $this->json($payload, $status)->send();
    }

    // ----------------------------------------------------------------
    // Redirects
    // ----------------------------------------------------------------

    public function redirect(string $url, int $status = 302): static
    {
        $this->statusCode = $status;
        $this->setHeader('Location', $url);
        $this->body = '';
        return $this;
    }

    public function back(int $status = 302): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        return $this->redirect($referer, $status);
    }

    public function redirectRoute(string $name, array $params = [], int $status = 302): static
    {
        $router = Application::getInstance()->make(Router::class);
        $url    = $router->route($name, $params);
        return $this->redirect($url, $status);
    }

    // ----------------------------------------------------------------
    // File download
    // ----------------------------------------------------------------

    public function download(string $filePath, string $fileName = null): void
    {
        if (! file_exists($filePath)) {
            $this->json(['success' => false, 'message' => 'File not found.'], 404)->send();
            return;
        }

        $fileName  ??= basename($filePath);
        $mimeType  = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileSize  = filesize($filePath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Length: ' . $fileSize);
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    // ----------------------------------------------------------------
    // Security headers
    // ----------------------------------------------------------------

    public function withSecurityHeaders(): static
    {
        return $this->withHeaders([
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-Content-Type-Options'    => 'nosniff',
            'X-XSS-Protection'          => '1; mode=block',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=()',
        ]);
    }

    // ----------------------------------------------------------------
    // Send
    // ----------------------------------------------------------------

    public function send(): void
    {
        if (headers_sent()) {
            echo $this->body;
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }

    public function sendAndExit(): never
    {
        $this->send();
        exit;
    }
}
