<?php
namespace Api\Middleware;

use Api\Exception\UnauthorizedException;

class AuthMiddleware implements MiddlewareInterface {
    private $excludedPaths = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/forgot-password'
    ];
    
    public function handle($request, $next) {
        // 检查是否是排除的路径
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (in_array($path, $this->excludedPaths)) {
            return $next($request);
        }
        
        // 获取认证令牌
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
        
        if (!$token) {
            throw new UnauthorizedException('未提供认证令牌');
        }
        
        try {
            // 验证令牌
            $decoded = $this->validateToken($token);
            
            // 将用户信息添加到请求中
            $request->user = $decoded;
            
            return $next($request);
        } catch (\Exception $e) {
            throw new UnauthorizedException('无效的认证令牌');
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