<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\LogService;

class SecurityMiddleware implements MiddlewareInterface {
    private $logService;
    private $attackPatterns = [
        'sql_injection' => [
            '/(\s|\'|`|%20)(OR|AND|UNION|SELECT|INSERT|UPDATE|DELETE|DROP|ALTER)(\s|\'|`|%20)/i',
            '/(\'|\"|%27|%22)(.*?)(\'|\"|%27|%22)/i',
            '/;.*/i'
        ],
        'xss' => [
            '/<script.*?>.*?<\/script>/is',
            '/(javascript|vbscript):/i',
            '/on(load|click|mouseover|submit|focus|blur)=".*?"/i'
        ],
        'path_traversal' => [
            '/\.\.(\/|\\\\)/i',
            '/(\/|\\\\)\.\./',
        ],
        'command_injection' => [
            '/[;&|`].*$/i',
            '/\$\(.*\)/',
            '/`.*`/'
        ]
    ];

    public function __construct(LogService $logService) {
        $this->logService = $logService;
    }

    public function process(Request $request, RequestHandler $handler): Response {
        $attackDetected = $this->detectAttack($request);
        
        if ($attackDetected) {
            // 记录攻击
            $this->logService->logAttack([
                'ip_address' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'attack_type' => $attackDetected['type'],
                'request_method' => $request->getMethod(),
                'request_uri' => (string) $request->getUri(),
                'request_body' => (string) $request->getBody(),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'severity' => $attackDetected['severity']
            ]);

            // 根据攻击严重程度决定是否阻止请求
            if (in_array($attackDetected['severity'], ['high', 'critical'])) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => '检测到潜在的安全威胁'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            }
        }

        return $handler->handle($request);
    }

    private function detectAttack(Request $request): ?array {
        $params = array_merge(
            $request->getQueryParams(),
            $request->getParsedBody() ?? [],
            $request->getUploadedFiles()
        );

        $uri = (string) $request->getUri();
        $method = $request->getMethod();
        $headers = $request->getHeaders();
        $body = (string) $request->getBody();

        foreach ($this->attackPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                // 检查URI
                if (preg_match($pattern, $uri)) {
                    return [
                        'type' => $type,
                        'severity' => $this->determineAttackSeverity($type, $uri)
                    ];
                }

                // 检查请求参数
                foreach ($params as $param) {
                    if (is_string($param) && preg_match($pattern, $param)) {
                        return [
                            'type' => $type,
                            'severity' => $this->determineAttackSeverity($type, $param)
                        ];
                    }
                }

                // 检查请求体
                if (!empty($body) && preg_match($pattern, $body)) {
                    return [
                        'type' => $type,
                        'severity' => $this->determineAttackSeverity($type, $body)
                    ];
                }

                // 检查特定的请求头
                $sensitiveHeaders = ['User-Agent', 'Referer', 'Cookie'];
                foreach ($sensitiveHeaders as $header) {
                    if (isset($headers[$header]) && is_array($headers[$header])) {
                        foreach ($headers[$header] as $value) {
                            if (preg_match($pattern, $value)) {
                                return [
                                    'type' => $type,
                                    'severity' => $this->determineAttackSeverity($type, $value)
                                ];
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    private function determineAttackSeverity(string $type, string $content): string {
        // 基于攻击类型和内容特征确定严重程度
        $severity = 'low';

        // SQL注入检测
        if ($type === 'sql_injection') {
            if (stripos($content, 'UNION') !== false || 
                stripos($content, 'SELECT') !== false) {
                $severity = 'critical';
            } elseif (stripos($content, 'DROP') !== false || 
                     stripos($content, 'DELETE') !== false) {
                $severity = 'high';
            } elseif (stripos($content, 'UPDATE') !== false || 
                     stripos($content, 'INSERT') !== false) {
                $severity = 'medium';
            }
        }
        // XSS检测
        elseif ($type === 'xss') {
            if (stripos($content, '<script') !== false) {
                $severity = 'high';
            } elseif (stripos($content, 'javascript:') !== false) {
                $severity = 'medium';
            }
        }
        // 路径遍历检测
        elseif ($type === 'path_traversal') {
            if (substr_count($content, '../') > 2) {
                $severity = 'high';
            } elseif (stripos($content, 'etc/passwd') !== false || 
                     stripos($content, 'win.ini') !== false) {
                $severity = 'critical';
            }
        }
        // 命令注入检测
        elseif ($type === 'command_injection') {
            if (stripos($content, '&&') !== false || 
                stripos($content, '||') !== false) {
                $severity = 'critical';
            } elseif (stripos($content, ';') !== false) {
                $severity = 'high';
            }
        }

        return $severity;
    }
} 