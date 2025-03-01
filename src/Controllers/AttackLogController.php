<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AttackLogService;

class AttackLogController {
    private $attackLogService;

    public function __construct(AttackLogService $attackLogService) {
        $this->attackLogService = $attackLogService;
    }

    public function list(Request $request, Response $response): Response {
        try {
            $params = $request->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $filters = [
                'attack_type' => $params['attack_type'] ?? null,
                'severity' => $params['severity'] ?? null,
                'status' => $params['status'] ?? null,
                'start_date' => $params['start_date'] ?? null,
                'end_date' => $params['end_date'] ?? null,
                'ip_address' => $params['ip_address'] ?? null
            ];

            $result = $this->attackLogService->getAttackLogs($page, $limit, $filters);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit
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

    public function getStatistics(Request $request, Response $response): Response {
        try {
            $params = $request->getQueryParams();
            $timeRange = $params['time_range'] ?? 'day'; // day, week, month
            
            $stats = $this->attackLogService->getStatistics($timeRange);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $stats
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

    public function getDetails(Request $request, Response $response, array $args): Response {
        try {
            $logId = (int)$args['id'];
            $details = $this->attackLogService->getAttackLogDetails($logId);
            
            if (!$details) {
                throw new \Exception('日志记录不存在');
            }
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $details
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

    public function updateStatus(Request $request, Response $response, array $args): Response {
        try {
            $logId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['status'])) {
                throw new \Exception('状态参数缺失');
            }
            
            $this->attackLogService->updateAttackLogStatus($logId, $data['status']);
            
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => '状态更新成功'
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

    public function export(Request $request, Response $response): Response {
        try {
            $params = $request->getQueryParams();
            $filters = [
                'attack_type' => $params['attack_type'] ?? null,
                'severity' => $params['severity'] ?? null,
                'status' => $params['status'] ?? null,
                'start_date' => $params['start_date'] ?? null,
                'end_date' => $params['end_date'] ?? null
            ];
            
            $data = $this->attackLogService->exportAttackLogs($filters);
            
            // 设置CSV响应头
            $response = $response->withHeader('Content-Type', 'text/csv');
            $response = $response->withHeader('Content-Disposition', 'attachment; filename="attack_logs.csv"');
            
            // 写入CSV数据
            $output = fopen('php://temp', 'r+');
            
            // 写入CSV头
            fputcsv($output, ['ID', '攻击类型', '严重程度', 'IP地址', '请求路径', '状态', '创建时间']);
            
            // 写入数据行
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['attack_type'],
                    $row['severity'],
                    $row['ip_address'],
                    $row['request_uri'],
                    $row['status'],
                    $row['created_at']
                ]);
            }
            
            rewind($output);
            $response->getBody()->write(stream_get_contents($output));
            fclose($output);
            
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
} 