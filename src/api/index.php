<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../../vendor/autoload.php';

// 创建DI容器
$container = new Container();
AppFactory::setContainer($container);

// 创建应用
$app = AppFactory::create();

// 添加错误中间件
$app->addErrorMiddleware(true, true, true);

// 添加CORS中间件
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// 基础路由
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'status' => 'success',
        'message' => 'API服务正常运行'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// API路由组
$app->group('/api', function ($group) {
    // 认证路由
    $group->post('/auth/login', 'App\Controllers\AuthController:login');
    $group->post('/auth/logout', 'App\Controllers\AuthController:logout');
    
    // 用户管理路由
    $group->get('/users', 'App\Controllers\UserController:list');
    $group->post('/users', 'App\Controllers\UserController:create');
    $group->get('/users/{id}', 'App\Controllers\UserController:get');
    $group->put('/users/{id}', 'App\Controllers\UserController:update');
    $group->delete('/users/{id}', 'App\Controllers\UserController:delete');
    
    // 权限管理路由
    $group->get('/permissions', 'App\Controllers\PermissionController:list');
    $group->post('/permissions', 'App\Controllers\PermissionController:create');
    $group->delete('/permissions/{id}', 'App\Controllers\PermissionController:delete');
    
    // 攻击日志路由
    $group->get('/attack-logs', 'App\Controllers\AttackLogController:list');
    $group->get('/attack-logs/{id}', 'App\Controllers\AttackLogController:get');
    
    // 系统日志路由
    $group->get('/activity-logs', 'App\Controllers\ActivityLogController:list');
});

// 运行应用
$app->run(); 