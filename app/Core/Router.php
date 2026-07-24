<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/**
 * Router
 *
 * Supports: GET, POST, PUT, PATCH, DELETE
 * Route params: /tickets/{id}
 * Named routes: ->name('ticket.show')
 * Middleware groups
 * Route prefixes
 */
class Router
{
    private array  $routes      = [];
    private array  $namedRoutes = [];
    private array  $middleware  = [];
    private string $prefix      = '';
    private array  $groupMiddleware = [];

    // ----------------------------------------------------------------
    // Registration helpers
    // ----------------------------------------------------------------

    public function get(string $uri, array|callable $action): static
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|callable $action): static
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, array|callable $action): static
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, array|callable $action): static
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, array|callable $action): static
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /** Register a route that responds to any method */
    public function any(string $uri, array|callable $action): static
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    private function addRoute(string $method, string $uri, array|callable $action): static
    {
        $uri = $this->prefix . '/' . ltrim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        $route = [
            'method'     => $method,
            'uri'        => $uri,
            'action'     => $action,
            'middleware' => $this->groupMiddleware,
            'name'       => null,
        ];

        $this->routes[] = &$route;
        // Store reference so name() can update it
        $this->middleware[] = &$route;

        return $this;
    }

    /** Name the last registered route */
    public function name(string $name): static
    {
        $last = &$this->routes[array_key_last($this->routes)];
        $last['name'] = $name;
        $this->namedRoutes[$name] = &$last;
        return $this;
    }

    /** Add middleware to the last registered route */
    public function middleware(string|array $middleware): static
    {
        $last = &$this->routes[array_key_last($this->routes)];
        $list = is_array($middleware) ? $middleware : [$middleware];
        $last['middleware'] = array_merge($last['middleware'], $list);
        return $this;
    }

    // ----------------------------------------------------------------
    // Route Groups
    // ----------------------------------------------------------------

    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix     = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->prefix .= '/' . trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $extra = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $this->groupMiddleware = array_merge($this->groupMiddleware, $extra);
        }

        $callback($this);

        // Restore
        $this->prefix           = $previousPrefix;
        $this->groupMiddleware  = $previousMiddleware;
    }

    // ----------------------------------------------------------------
    // URL Generation
    // ----------------------------------------------------------------

    /** Generate URL for a named route */
    public function route(string $name, array $params = []): string
    {
        if (! isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]['uri'];

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
            $uri = str_replace('{' . $key . '?}', (string) $value, $uri);
        }

        // Remove optional params that weren't supplied
        $uri = preg_replace('/\/\{[^}]+\?\}/', '', $uri);

        $base = rtrim(env('APP_URL', ''), '/');
        return $base . $uri;
    }

    // ----------------------------------------------------------------
    // Dispatch
    // ----------------------------------------------------------------

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri    = $request->pathInfo();

        // Support method override via _method field or X-HTTP-Method-Override header
        if ($method === 'POST') {
            $override = $request->input('_method')
                ?? $request->header('X-HTTP-Method-Override');
            if ($override) {
                $method = strtoupper($override);
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchUri($route['uri'], $uri);

            if ($params !== false) {
                $this->runMiddleware($route['middleware'], $request, function () use ($route, $params, $request) {
                    $this->callAction($route['action'], $params, $request);
                });
                return;
            }
        }

        // No route matched
        $this->handleNotFound($uri, $request);
    }

    /**
     * Match a route URI pattern against the actual URI.
     * Returns assoc array of captured params, or false on no match.
     */
    private function matchUri(string $pattern, string $uri): array|false
    {
        // Convert {param} and {param?} to regex groups
        $regex = preg_replace('/\{([a-zA-Z_]+)\?\}/', '(?P<$1>[^/]*)?', $pattern);
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/',   '(?P<$1>[^/]+)',  $regex);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Filter out numeric keys from match result
            return array_filter(
                $matches,
                fn($k) => is_string($k),
                ARRAY_FILTER_USE_KEY
            );
        }

        return false;
    }

    // ----------------------------------------------------------------
    // Middleware Pipeline
    // ----------------------------------------------------------------

    private function runMiddleware(array $stack, Request $request, callable $final): void
    {
        if (empty($stack)) {
            $final();
            return;
        }

        $middlewareClass = array_shift($stack);
        $middleware      = $this->resolveMiddleware($middlewareClass);

        $middleware->handle($request, function () use ($stack, $request, $final) {
            $this->runMiddleware($stack, $request, $final);
        });
    }

    private function resolveMiddleware(string $alias): object
    {
        $map = [
            'auth'     => \App\Middleware\AuthMiddleware::class,
            'csrf'     => \App\Middleware\CsrfMiddleware::class,
            'role'     => \App\Middleware\RoleMiddleware::class,
            'api.auth' => \App\Middleware\ApiAuthMiddleware::class,
        ];

        // If it's already a FQCN
        $class = $map[$alias] ?? $alias;

        if (! class_exists($class)) {
            throw new \RuntimeException("Middleware [{$class}] not found.");
        }

        return new $class();
    }

    // ----------------------------------------------------------------
    // Action Invocation
    // ----------------------------------------------------------------

    private function callAction(array|callable $action, array $params, Request $request): void
    {
        if (is_callable($action)) {
            echo $action($request, ...array_values($params));
            return;
        }

        // [$controllerClass, 'method'] format
        [$controllerClass, $method] = $action;

        if (! class_exists($controllerClass)) {
            throw new \RuntimeException("Controller [{$controllerClass}] not found.");
        }

        $controller = new $controllerClass();

        if (! method_exists($controller, $method) && ! method_exists($controller, '__call')) {
            throw new \RuntimeException("Method [{$method}] not found on [{$controllerClass}].");
        }

        echo $controller->$method($request, ...array_values($params));
    }

    // ----------------------------------------------------------------
    // 404
    // ----------------------------------------------------------------

    private function handleNotFound(string $uri, Request $request): void
    {
        http_response_code(404);

        if ($this->isApiRequest($uri)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Endpoint not found.']);
            return;
        }

        // Check if a route exists but wrong method
        foreach ($this->routes as $route) {
            if ($this->matchUri($route['uri'], $uri) !== false) {
                http_response_code(405);
                $app = Application::getInstance();
                echo $app->handleException(
                    new HttpException(405, 'Method Not Allowed')
                );
                return;
            }
        }

        include Application::getInstance()->path('resources/views/errors/404.php');
    }

    private function isApiRequest(string $uri): bool
    {
        return str_starts_with($uri, '/api/');
    }

    // ----------------------------------------------------------------
    // Accessors
    // ----------------------------------------------------------------

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
