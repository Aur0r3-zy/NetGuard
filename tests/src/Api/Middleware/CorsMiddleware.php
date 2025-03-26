<?php

namespace Api\Middleware;

class CorsMiddleware implements MiddlewareInterface {
    public function handle($request, $next) {
        // 处理预检请求
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
            exit(0);
        }
        
        // 继续处理请求
        return $next($request);
    }
} 