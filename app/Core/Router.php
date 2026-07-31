<?php
declare(strict_types=1);
namespace App\Core;

class Router {
    private array  $routes      = [];
    private array  $namedRoutes = [];
    private string $prefix      = '';
    private array  $groupMiddleware = [];

    public function get(string $uri, array|callable $action): static    { return $this->addRoute('GET',$uri,$action); }
    public function post(string $uri, array|callable $action): static   { return $this->addRoute('POST',$uri,$action); }
    public function put(string $uri, array|callable $action): static    { return $this->addRoute('PUT',$uri,$action); }
    public function patch(string $uri, array|callable $action): static  { return $this->addRoute('PATCH',$uri,$action); }
    public function delete(string $uri, array|callable $action): static { return $this->addRoute('DELETE',$uri,$action); }

    private function addRoute(string $method, string $uri, array|callable $action): static {
        $uri = $this->prefix.'/'.ltrim($uri,'/');
        $uri = rtrim($uri,'/') ?: '/';
        $this->routes[] = ['method'=>$method,'uri'=>$uri,'action'=>$action,'middleware'=>$this->groupMiddleware,'name'=>null];
        return $this;
    }
    public function name(string $name): static {
        $last = &$this->routes[array_key_last($this->routes)];
        $last['name']=$name; $this->namedRoutes[$name]=&$last; return $this;
    }
    public function middleware(string|array $middleware): static {
        $last = &$this->routes[array_key_last($this->routes)];
        $list = is_array($middleware)?$middleware:[$middleware];
        $last['middleware']=array_merge($last['middleware'],$list); return $this;
    }
    public function group(array $attributes, callable $callback): void {
        $prevPrefix=$this->prefix; $prevMw=$this->groupMiddleware;
        if (isset($attributes['prefix'])) $this->prefix.='/'.trim($attributes['prefix'],'/');
        if (isset($attributes['middleware'])) {
            $extra=is_array($attributes['middleware'])?$attributes['middleware']:[$attributes['middleware']];
            $this->groupMiddleware=array_merge($this->groupMiddleware,$extra);
        }
        $callback($this);
        $this->prefix=$prevPrefix; $this->groupMiddleware=$prevMw;
    }
    public function route(string $name, array $params=[]): string {
        if (!isset($this->namedRoutes[$name])) throw new \InvalidArgumentException("Route [{$name}] not defined.");
        $uri=$this->namedRoutes[$name]['uri'];
        foreach ($params as $k=>$v) { $uri=str_replace('{'.$k.'}',(string)$v,$uri); $uri=str_replace('{'.$k.'?}',(string)$v,$uri); }
        $uri=preg_replace('/\/\{[^}]+\?\}/','',$uri);
        return rtrim(env('APP_URL',''),'/').$uri;
    }
    public function dispatch(Request $request): void {
        $method=$request->method(); $uri=$request->pathInfo();
        if ($method==='POST') {
            $override=$request->input('_method')??$request->header('X-HTTP-Method-Override');
            if ($override) $method=strtoupper($override);
        }
        foreach ($this->routes as $route) {
            if ($route['method']!==$method) continue;
            $params=$this->matchUri($route['uri'],$uri);
            if ($params!==false) {
                $this->runMiddleware($route['middleware'],$request,function() use($route,$params,$request){
                    $this->callAction($route['action'],$params,$request);
                });
                return;
            }
        }
        http_response_code(404);
        $p404=Application::getInstance()->path('resources/views/errors/404.php');
        file_exists($p404) ? include $p404 : print('<h1>404 Not Found</h1>');
    }
    private function matchUri(string $pattern, string $uri): array|false {
        $regex=preg_replace('/\{([a-zA-Z_]+)\?\}/','(?P<$1>[^/]*)?',$pattern);
        $regex=preg_replace('/\{([a-zA-Z_]+)\}/','(?P<$1>[^/]+)',$regex);
        $regex='#^'.$regex.'$#';
        if (preg_match($regex,$uri,$matches)) return array_filter($matches,fn($k)=>is_string($k),ARRAY_FILTER_USE_KEY);
        return false;
    }
    private function runMiddleware(array $stack, Request $request, callable $final): void {
        if (empty($stack)) { $final(); return; }
        $mwClass=array_shift($stack);
        $map=['auth'=>\App\Middleware\AuthMiddleware::class,'csrf'=>\App\Middleware\CsrfMiddleware::class,'role'=>\App\Middleware\RoleMiddleware::class,'api.auth'=>\App\Middleware\ApiAuthMiddleware::class];
        $class=$map[$mwClass]??$mwClass;
        (new $class())->handle($request,function() use($stack,$request,$final){ $this->runMiddleware($stack,$request,$final); });
    }
    private function callAction(array|callable $action, array $params, Request $request): void {
        if (is_callable($action)) { echo $action($request,...array_values($params)); return; }
        [$controllerClass,$method]=$action;
        $controller=new $controllerClass();
        echo $controller->$method($request,...array_values($params));
    }
    public function getRoutes(): array { return $this->routes; }
}
