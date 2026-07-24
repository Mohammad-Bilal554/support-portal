<?php

declare(strict_types=1);

namespace App\Core;

/**
 * BaseController
 *
 * All controllers extend this.
 * Provides view rendering, response helpers,
 * redirect shortcuts, auth shortcuts, and JSON output.
 */
abstract class Controller
{
    protected Response $response;
    protected Session  $session;

    public function __construct()
    {
        $this->response = new Response();
        $this->session  = Session::getInstance();
    }

    // ----------------------------------------------------------------
    // View rendering
    // ----------------------------------------------------------------

    /**
     * Render a view and return HTML string.
     *
     * @param string $view  Dot-notation path: 'admin.dashboard'
     * @param array  $data  Variables passed to the template
     */
    protected function view(string $view, array $data = []): string
    {
        // Always share auth user with views
        $user = $this->session->getUser();
        if ($user) {
            $data['authUser'] = $user;
        }

        return View::make($view, $data)->render();
    }

    /**
     * Render a view inside a layout.
     *
     * @param string $view
     * @param string $layout  e.g. 'layouts.app'
     * @param array  $data
     */
    protected function viewWithLayout(string $view, string $layout, array $data = []): string
    {
        $user = $this->session->getUser();
        if ($user) {
            $data['authUser'] = $user;
        }

        return View::make($view, $data)->layout($layout)->render();
    }

    // ----------------------------------------------------------------
    // JSON responses
    // ----------------------------------------------------------------

    protected function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function jsonSuccess(mixed $data = null, string $message = 'Success', int $status = 200): string
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function jsonError(string $message = 'Error', int $status = 400, array $errors = []): string
    {
        $payload = ['success' => false, 'message' => $message];
        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }
        return $this->json($payload, $status);
    }

    // ----------------------------------------------------------------
    // Redirects
    // ----------------------------------------------------------------

    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    protected function redirectBack(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        $this->redirect($referer);
    }

    protected function redirectRoute(string $name, array $params = []): never
    {
        $url = Application::getInstance()->make(Router::class)->route($name, $params);
        $this->redirect($url);
    }

    // ----------------------------------------------------------------
    // Flash + redirect combos
    // ----------------------------------------------------------------

    protected function withSuccess(string $message, string $url = null): never
    {
        $this->session->success($message);
        if ($url) {
            $this->redirect($url);
        }
        $this->redirectBack();
    }

    protected function withError(string $message, string $url = null): never
    {
        $this->session->error($message);
        if ($url) {
            $this->redirect($url);
        }
        $this->redirectBack();
    }

    // ----------------------------------------------------------------
    // Auth shortcuts
    // ----------------------------------------------------------------

    protected function auth(): ?array
    {
        return $this->session->getUser();
    }

    protected function authId(): ?int
    {
        return $this->session->getUserId();
    }

    protected function authRole(): ?string
    {
        return $this->session->getUserRole();
    }

    protected function isAdmin(): bool
    {
        return $this->authRole() === 'super_admin';
    }

    protected function isEmployee(): bool
    {
        return in_array($this->authRole(), ['super_admin', 'employee']);
    }

    protected function isClient(): bool
    {
        return $this->authRole() === 'client';
    }

    /** Abort with HTTP error if condition fails */
    protected function authorize(bool $condition, int $status = 403, string $message = 'Forbidden'): void
    {
        if (! $condition) {
            http_response_code($status);

            if ($this->isApiRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }

            $view = Application::getInstance()->path("resources/views/errors/{$status}.php");
            if (file_exists($view)) {
                include $view;
            } else {
                echo "<h1>{$status} {$message}</h1>";
            }
            exit;
        }
    }

    protected function requireLogin(): void
    {
        if (! $this->session->isLoggedIn()) {
            $this->session->setFlash('intended_url', current_url());
            $this->redirect(url('auth/login'));
        }
    }

    // ----------------------------------------------------------------
    // Request helpers
    // ----------------------------------------------------------------

    protected function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/api/');
    }

    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    // ----------------------------------------------------------------
    // Abort helpers
    // ----------------------------------------------------------------

    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message ?: 'Error ' . $code]);
            exit;
        }

        $errorPage = Application::getInstance()->path("resources/views/errors/{$code}.php");
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<h1>{$code}</h1><p>{$message}</p>";
        }
        exit;
    }
}
