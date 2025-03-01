<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\UserService;

class PermissionMiddleware implements MiddlewareInterface {
    private $userService;
    private $routePermissions = [
        'GET' => [
            '/api/users' => ['view_users'],
            '/api/users/{id}' => ['view_users'],
            '/api/permissions' => ['manage_permissions'],
            '/api/attack-logs' => ['view_security_logs'],
            '/api/activity-logs' => ['view_activity_logs']
        ],
        'POST' => [
            '/api/users' => ['manage_users'],
            '/api/permissions' => ['manage_permissions']
        ],
        'PUT' => [
            '/api/users/{id}' => ['manage_users']
        ],
        'DELETE' => [
            '/api/users/{id}' => ['manage_users'],
            '/api/permissions/{id}' => ['manage_permissions']
        ]
    ];

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function process(Request $request, RequestHandler $handler): Response {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return $this->createErrorResponse('未授权访问', 401);
        }

        $method = $request->getMethod();
        $path = $this->normalizePath($request->getUri()->getPath());

        // 检查路由是否需要权限
        $requiredPermissions = $this->getRequiredPermissions($method, $path);
        if (empty($requiredPermissions)) {
            return $handler->handle($request);
        }

        // 获取用户权限
        $userPermissions = $this->userService->getUserPermissions($userId);
        $userPermissionNames = array_column($userPermissions, 'name');

        // 检查是否有所需权限
        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $userPermissionNames)) {
                return $this->createErrorResponse('权限不足', 403);
            }
        }

        return $handler->handle($request);
    }

    private function normalizePath(string $path): string {
        // 将路径参数替换为通配符
        return preg_replace('/\/\d+/', '/{id}', $path);
    }

    private function getRequiredPermissions(string $method, string $path): array {
        return $this->routePermissions[$method][$path] ?? [];
    }

    private function createErrorResponse(string $message, int $status): Response {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $message
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
} 