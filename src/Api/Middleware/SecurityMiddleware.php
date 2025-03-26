<?php
namespace App\Api\Middleware;

class SecurityMiddleware extends Middleware {
    public function handle(string $request, string $method): void {
        // 检查请求方法
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])) {
            http_response_code(405);
            die(json_encode(['error' => '不支持的请求方法']));
        }

        // 检查请求头
        $headers = getallheaders();
        if (!isset($headers['Content-Type']) || $headers['Content-Type'] !== 'application/json') {
            http_response_code(415);
            die(json_encode(['error' => '不支持的媒体类型']));
        }

        // 防止XSS攻击
        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $_POST = $this->sanitizeInput($input);
            }
        }
    }

    private function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
} 