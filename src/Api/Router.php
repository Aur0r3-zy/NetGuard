<?php
namespace App\Api;

use App\Api\Exception\RouteNotFoundException;

class Router {
    private array $routes = [];
    private $middlewares = [];
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }
    
    public function dispatch($method, $uri) {
        // 移除查询字符串
        $path = parse_url($uri, PHP_URL_PATH);
        
        // 查找匹配的路由
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                // 创建请求对象
                $request = new Request($path, $method);
                $request->params = $params;
                
                // 应用中间件
                $handler = $route['handler'];
                foreach (array_reverse($this->middlewares) as $middleware) {
                    $handler = function($request) use ($middleware, $handler) {
                        return $middleware->handle($request, $handler);
                    };
                }
                
                // 执行处理程序
                return $handler($request);
            }
        }
        
        // 未找到路由
        throw new RouteNotFoundException();
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    private function matchPath($routePath, $requestPath, &$params) {
        // 将路由路径转换为正则表达式
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $requestPath, $matches)) {
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }
        
        return false;
    }
} 