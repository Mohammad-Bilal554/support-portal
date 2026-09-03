<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Database;
use App\Core\Request;
use App\Models\User;

/**
 * Auth API Controller
 *
 * POST /api/v1/auth/token        Generate a new API token (login with email+password)
 * POST /api/v1/auth/revoke       Revoke current token
 * GET  /api/v1/auth/tokens       List all tokens for current user
 */
class AuthApiController extends ApiController
{
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request = $request;
    }

    /**
     * POST /api/v1/auth/token
     * Generate API token — does NOT require existing token.
     */
    public function token(Request $request): string
    {
        $email    = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');
        $name     = trim((string)$request->input('name', 'API Token'));
        $expiresIn= (int)$request->input('expires_in', 0); // days, 0 = never

        if (!$email || !$password) {
            return $this->error('email and password are required.', 422);
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->error('Invalid credentials.', 401);
        }

        if (!$user['is_active']) {
            return $this->error('Account is inactive.', 401);
        }

        $db         = Database::getInstance();
        $plainToken = bin2hex(random_bytes(32)); // 64-char random token
        $hashedToken = hash('sha256', $plainToken);

        $expiresAt = $expiresIn > 0
            ? date('Y-m-d H:i:s', strtotime("+{$expiresIn} days"))
            : null;

        $tokenId = $db->insert('api_tokens', [
            'user_id'     => (int)$user['id'],
            'name'        => $name,
            'token'       => $hashedToken,
            'is_active'   => 1,
            'expires_at'  => $expiresAt,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success([
            'token'      => $plainToken,   // Only shown ONCE — store it securely
            'token_id'   => (int)$tokenId,
            'name'       => $name,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'    => (int)$user['id'],
                'name'  => trim($user['first_name'] . ' ' . $user['last_name']),
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ], 'API token generated. Store it securely — it will not be shown again.');
    }

    /**
     * POST /api/v1/auth/revoke
     * Revoke the current Bearer token.
     */
    public function revoke(Request $request): string
    {
        $rawToken    = $this->extractBearerToken($request);
        $hashedToken = hash('sha256', $rawToken);

        Database::getInstance()->update(
            'api_tokens',
            ['is_active' => 0],
            ['token' => $hashedToken]
        );

        return $this->success(null, 'Token revoked successfully.');
    }

    /**
     * GET /api/v1/auth/tokens
     * List all tokens for the authenticated user.
     */
    public function tokens(Request $request): string
    {
        $db     = Database::getInstance();
        $tokens = $db->fetchAll(
            "SELECT id, name, is_active, last_used_at, expires_at, created_at
             FROM api_tokens
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$this->userId()]
        );

        $formatted = array_map(fn($t) => [
            'id'          => (int)$t['id'],
            'name'        => $t['name'],
            'is_active'   => (bool)$t['is_active'],
            'last_used_at'=> $t['last_used_at'],
            'expires_at'  => $t['expires_at'],
            'is_expired'  => !empty($t['expires_at']) && strtotime($t['expires_at']) < time(),
            'created_at'  => $t['created_at'],
        ], $tokens);

        return $this->success($formatted, 'Tokens retrieved.');
    }

    private function extractBearerToken(Request $request): string
    {
        $header = $request->header('Authorization') ?? '';
        return str_starts_with($header, 'Bearer ')
            ? trim(substr($header, 7))
            : '';
    }
}
