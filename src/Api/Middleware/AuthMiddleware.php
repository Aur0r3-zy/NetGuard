<?php
namespace App\Api\Middleware;

use App\Api\Request;

class AuthMiddleware extends Middleware {
    private $excludedPaths = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/forgot-password'
    ];
    
    public function handle(string $request, string $method): void {
        $requestObj = new Request($request, $method);
        
        // 检查是否是排除的路径
        if (in_array($requestObj->path, $this->excludedPaths)) {
            return;
        }
        
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        if (empty($token)) {
            http_response_code(401);
            die(json_encode(['error' => '未提供认证令牌']));
        }
        
        try {
            // 验证令牌
            $decoded = $this->validateToken($token);
            
            // 将用户信息添加到请求中
            $requestObj->user = $decoded;
        } catch (\Exception $e) {
            http_response_code(401);
            die(json_encode(['error' => '无效的认证令牌']));
        }
    }
    
    private function validateToken($token) {
        // TODO: 实现JWT令牌验证
        // 这里应该使用JWT库来验证令牌
        // 返回解码后的用户信息
        return [
            'id' => 1,
            'username' => 'admin',
            'role' => 'admin'
        ];
    }
} 