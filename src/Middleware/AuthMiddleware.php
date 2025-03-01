<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware implements MiddlewareInterface {
    private $jwtSecret;
    private $publicPaths = [
        '/api/auth/login' => ['POST'],
        '/api/auth/register' => ['POST'],
        '/' => ['GET']
    ];

    public function __construct(string $jwtSecret) {
        $this->jwtSecret = $jwtSecret;
    }

    public function process(Request $request, RequestHandler $handler): Response {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // 检查是否是公开路径
        if ($this->isPublicPath($path, $method)) {
            return $handler->handle($request);
        }

        // 获取并验证JWT令牌
        $token = $this->extractToken($request);
        if (!$token) {
            return $this->createErrorResponse('未提供认证令牌', 401);
        }

        try {
            // 验证令牌
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // 将用户信息添加到请求属性中
            $request = $request->withAttribute('user_id', $payload->sub);
            $request = $request->withAttribute('user_role', $payload->role);
            
            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->createErrorResponse('无效的认证令牌', 401);
        }
    }

    private function isPublicPath(string $path, string $method): bool {
        return isset($this->publicPaths[$path]) && 
               in_array($method, $this->publicPaths[$path]);
    }

    private function extractToken(Request $request): ?string {
        $header = $request->getHeaderLine('Authorization');
        if (empty($header)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
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