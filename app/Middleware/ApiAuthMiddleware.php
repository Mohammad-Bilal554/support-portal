<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;

/**
 * ApiAuthMiddleware
 *
 * Validates Bearer tokens on every API request.
 * Sets the authenticated user in request attributes.
 * Enforces rate limiting per token (60 requests/minute).
 */
class ApiAuthMiddleware
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function handle(Request $request, callable $next): mixed
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorized('No API token provided. Use: Authorization: Bearer <token>');
        }

        // Look up token
        $apiToken = $this->db->fetchOne(
            "SELECT t.*, u.id AS user_id, u.first_name, u.last_name,
                    u.email, u.role, u.is_active, u.company_id
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.is_active = 1",
            [hash('sha256', $token)]
        );

        if (!$apiToken) {
            return $this->unauthorized('Invalid or revoked API token.');
        }

        if (!$apiToken['is_active']) {
            return $this->unauthorized('Your account is inactive.');
        }

        // Check expiry
        if (!empty($apiToken['expires_at']) && strtotime($apiToken['expires_at']) < time()) {
            return $this->unauthorized('API token has expired.');
        }

        // Rate limiting — 60 requests per minute per token
        if (!$this->checkRateLimit((int)$apiToken['id'])) {
            return $this->tooManyRequests();
        }

        // Update last_used_at
        $this->db->update('api_tokens', [
            'last_used_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int)$apiToken['id']]);

        // Attach user to request
        $request->setUser([
            'id'         => (int)$apiToken['user_id'],
            'first_name' => $apiToken['first_name'],
            'last_name'  => $apiToken['last_name'],
            'email'      => $apiToken['email'],
            'role'       => $apiToken['role'],
            'is_active'  => (bool)$apiToken['is_active'],
            'company_id' => $apiToken['company_id'],
        ]);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        // Authorization: Bearer <token>
        $header = $request->header('Authorization') ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }
        // Fallback: ?api_token=xxx
        return $request->query('api_token') ?: null;
    }

    private function checkRateLimit(int $tokenId): bool
    {
        $key     = "rate_limit_{$tokenId}";
        $window  = 60;   // seconds
        $maxReqs = 60;   // per window

        // Use DB-based rate limiting (works without Redis/APCu)
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs
             WHERE entity_id = ? AND entity_type = 'api_token'
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$tokenId, $window]
        );

        if ((int)$count >= $maxReqs) {
            return false;
        }

        // Log this request
        try {
            $this->db->insert('activity_logs', [
                'user_id'     => 0,
                'action'      => 'api_request',
                'entity_type' => 'api_token',
                'entity_id'   => $tokenId,
                'description' => 'API request',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Non-fatal
        }

        return true;
    }

    private function unauthorized(string $message): string
    {
        http_response_code(401);
        header('Content-Type: application/json');
        header('WWW-Authenticate: Bearer');
        echo json_encode([
            'success' => false,
            'error'   => 'Unauthorized',
            'message' => $message,
        ]);
        exit;
    }

    private function tooManyRequests(): string
    {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: 60');
        echo json_encode([
            'success' => false,
            'error'   => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Maximum 60 requests per minute.',
        ]);
        exit;
    }
}
