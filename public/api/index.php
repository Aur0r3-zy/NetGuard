<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Api\Router;
use Api\Middleware\AuthMiddleware;
use Api\Middleware\CorsMiddleware;

// 加载配置
$config = require __DIR__ . '/../../config/app.php';

// 初始化路由
$router = new Router();

// 注册中间件
$router->addMiddleware(new CorsMiddleware());
$router->addMiddleware(new AuthMiddleware());

// 加载路由配置
require __DIR__ . '/../../src/Api/routes.php';

// 处理请求
try {
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    
    // 设置响应头
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // 输出响应
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    // 错误处理
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器内部错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 