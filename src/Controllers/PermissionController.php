<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\PermissionService;
use App\Services\LogService;

class PermissionController {
    private $permissionService;
    private $logService;

    public function __construct(PermissionService $permissionService, LogService $logService) {
        $this->permissionService = $permissionService;
        $this->logService = $logService;
    }

    public function list(Request $request, Response $response): Response {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $permissions
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function create(Request $request, Response $response): Response {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['name']) || !isset($data['description'])) {
                throw new \Exception('缺少必要字段');
            }

            $permission = $this->permissionService->createPermission($data);
            
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '创建权限',
                "创建了新权限: {$data['name']}"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $permission
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        try {
            $this->permissionService->deletePermission($args['id']);
            
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '删除权限',
                "删除了权限ID: {$args['id']}"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => '权限已删除'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function assignToUser(Request $request, Response $response, array $args): Response {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['permissions']) || !is_array($data['permissions'])) {
                throw new \Exception('无效的权限数据');
            }

            $this->permissionService->assignPermissionsToUser($args['user_id'], $data['permissions']);
            
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '分配权限',
                "为用户ID {$args['user_id']} 分配了新的权限"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => '权限分配成功'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
} 