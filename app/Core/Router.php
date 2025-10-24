<?php
namespace App\Core;

use App\Middleware\MiddlewareInterface;

class Router {
    protected array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => []
    ];

    public function get(string $p, $h, array $m = []): void { $this->add('GET', $p, $h, $m); }
    public function post(string $p, $h, array $m = []): void { $this->add('POST', $p, $h, $m); }
    public function put(string $p, $h, array $m = []): void { $this->add('PUT', $p, $h, $m); }
    public function patch(string $p, $h, array $m = []): void { $this->add('PATCH', $p, $h, $m); }
    public function delete(string $p, $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    public function group(string $prefix, callable $cb, array $mws = []): void {
        $g = new class($this, rtrim($prefix, '/'), $mws) {
            private $r;
            private $pre;
            private $mws;

            public function __construct($r, $pre, $mws){
                $this->r = $r;
                $this->pre = $pre;
                $this->mws = $mws;
            }

            public function get($p, $h, $mw = []) { $this->r->get($this->pre.$p, $h, array_merge($this->mws, $mw)); }
            public function post($p, $h, $mw = []) { $this->r->post($this->pre.$p, $h, array_merge($this->mws, $mw)); }
            public function put($p, $h, $mw = []) { $this->r->put($this->pre.$p, $h, array_merge($this->mws, $mw)); }
            public function patch($p, $h, $mw = []) { $this->r->patch($this->pre.$p, $h, array_merge($this->mws, $mw)); }
            public function delete($p, $h, $mw = []) { $this->r->delete($this->pre.$p, $h, array_merge($this->mws, $mw)); }
        };
        $cb($g);
    }

    protected function add(string $m, string $p, $h, array $mw): void {
        $p = '/'.ltrim($p, '/');
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $p);
        $pattern = '#^'.rtrim($pattern,'/').'/?$#';
        $this->routes[$m][] = compact('p', 'pattern', 'h', 'mw');
    }

    public function dispatch(Request $req, Response $res): void {
        $method = $req->method;
        $uri = rtrim($req->uri, '/') ?: '/';

        foreach($this->routes[$method] ?? [] as $r) {
            if(preg_match($r['pattern'], $uri, $m)) {
                foreach($m as $k => $v)
                    if(!is_int($k)) $req->params[$k] = $v;

                $call = $this->resolve($r['h']);
                $pipe = array_reverse($this->mws($r['mw']));

                $next = function() use ($call, $req, $res) { 
                    return call_user_func($call, $req, $res); 
                };

                foreach($pipe as $mw){
                    $next = function() use ($mw, $req, $res, $next) { 
                        return $mw->handle($req, $next, $res); 
                    };
                }

                $next();
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    protected function resolve($h): callable {
        if(is_callable($h)) return $h;

        if(is_array($h) && count($h) === 2){
            [$c, $m] = $h;
            $i = new $c();
            return [$i, $m];
        }

        if(is_string($h) && str_contains($h, '@')){
            [$c, $m] = explode('@', $h, 2);
            $i = new $c();
            return [$i, $m];
        }

        throw new \InvalidArgumentException('Handler inválido');
    }

    protected function mws(array $mws): array {
        $out = [];
        foreach($mws as $mw){
            if(is_string($mw)){
                $map = [
                    'auth'    => \App\Middleware\AuthMiddleware::class,
                    'guest'   => \App\Middleware\GuestMiddleware::class,
                    'json'    => \App\Middleware\JsonMiddleware::class,
                    'cashier' => \App\Middleware\CashierMiddleware::class
                ];
                $mw = $map[$mw] ?? $mw;
            }

            $i = is_object($mw) ? $mw : new $mw();
            if(!$i instanceof MiddlewareInterface)
                throw new \RuntimeException('Middleware inválido');

            $out[] = $i;
        }
        return $out;
    }
}
