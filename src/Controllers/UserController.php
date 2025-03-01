<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\UserService;
use App\Services\LogService;

class UserController {
    private $userService;
    private $logService;

    public function __construct(UserService $userService, LogService $logService) {
        $this->userService = $userService;
        $this->logService = $logService;
    }

    public function list(Request $request, Response $response): Response {
        try {
            $users = $this->userService->getAllUsers();
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $users
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
            
            // 验证必要字段
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                throw new \Exception('缺少必要字段');
            }

            $user = $this->userService->createUser($data);
            
            // 记录操作日志
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '创建用户',
                "创建了新用户: {$data['username']}"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $user
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

    public function get(Request $request, Response $response, array $args): Response {
        try {
            $user = $this->userService->getUserById($args['id']);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $user
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $user = $this->userService->updateUser($args['id'], $data);
            
            // 记录操作日志
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '更新用户',
                "更新了用户ID: {$args['id']}"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $user
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
            $this->userService->deleteUser($args['id']);
            
            // 记录操作日志
            $this->logService->logActivity(
                $request->getAttribute('user_id'),
                '删除用户',
                "删除了用户ID: {$args['id']}"
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => '用户已删除'
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