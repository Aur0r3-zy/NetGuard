<?php

namespace Core\Log;

class LogManager {
    private $db;
    private $logger;
    private $logLevel;
    
    public function __construct($db, $logger, $logLevel = 'info') {
        $this->db = $db;
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }
    
    public function logMessage($message, $type = 'info', $context = []) {
        try {
            // 验证日志级别
            if (!$this->shouldLog($type)) {
                return false;
            }
            
            // 准备日志数据
            $logData = [
                'message' => $message,
                'type' => $type,
                'context' => json_encode($context),
                'timestamp' => time(),
                'user_id' => $context['user_id'] ?? null,
                'ip_address' => $context['ip_address'] ?? null
            ];
            
            // 保存到数据库
            $this->saveToDatabase($logData);
            
            // 写入文件日志
            $this->writeToFile($logData);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('日志记录失败：' . $e->getMessage());
            return false;
        }
    }
    
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        try {
            $query = "SELECT * FROM system_logs WHERE 1=1";
            $params = [];
            
            // 应用过滤器
            if (!empty($filters['type'])) {
                $query .= " AND type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['start_time'])) {
                $query .= " AND timestamp >= ?";
                $params[] = $filters['start_time'];
            }
            
            if (!empty($filters['end_time'])) {
                $query .= " AND timestamp <= ?";
                $params[] = $filters['end_time'];
            }
            
            if (!empty($filters['user_id'])) {
                $query .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            // 添加排序和限制
            $query .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            // 执行查询
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $logs = [];
            while ($row = $stmt->fetch()) {
                $row['context'] = json_decode($row['context'], true);
                $logs[] = $row;
            }
            
            return [
                'status' => 'success',
                'data' => $logs
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取日志失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取日志失败：' . $e->getMessage()
            ];
        }
    }
    
    public function clearLogs($before = null) {
        try {
            $query = "DELETE FROM system_logs";
            $params = [];
            
            if ($before !== null) {
                $query .= " WHERE timestamp < ?";
                $params[] = $before;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return [
                'status' => 'success',
                'message' => '日志清理成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('日志清理失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '日志清理失败：' . $e->getMessage()
            ];
        }
    }
    
    public function exportLogs($format = 'csv', $filters = []) {
        try {
            // 获取日志数据
            $logs = $this->getLogs($filters);
            
            if ($logs['status'] !== 'success') {
                throw new \Exception($logs['message']);
            }
            
            // 根据格式导出
            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($logs['data']);
                case 'json':
                    return $this->exportToJson($logs['data']);
                default:
                    throw new \Exception('不支持的导出格式');
            }
        } catch (\Exception $e) {
            $this->logger->error('日志导出失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '日志导出失败：' . $e->getMessage()
            ];
        }
    }
    
    private function shouldLog($type) {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        ];
        
        return ($levels[$type] ?? 0) >= ($levels[$this->logLevel] ?? 1);
    }
    
    private function saveToDatabase($logData) {
        $query = "INSERT INTO system_logs (message, type, context, timestamp, user_id, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $logData['message'],
            $logData['type'],
            $logData['context'],
            $logData['timestamp'],
            $logData['user_id'],
            $logData['ip_address']
        ]);
    }
    
    private function writeToFile($logData) {
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s', $logData['timestamp']),
            strtoupper($logData['type']),
            $logData['message'],
            !empty($logData['context']) ? json_encode($logData['context']) : ''
        );
        
        file_put_contents(
            __DIR__ . '/../../logs/system.log',
            $logEntry,
            FILE_APPEND
        );
    }
    
    private function exportToCsv($logs) {
        $filename = 'logs_' . date('Y-m-d_His') . '.csv';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        // 创建CSV文件
        $fp = fopen($filepath, 'w');
        
        // 写入表头
        fputcsv($fp, ['时间', '类型', '消息', '上下文', '用户ID', 'IP地址']);
        
        // 写入数据
        foreach ($logs as $log) {
            fputcsv($fp, [
                date('Y-m-d H:i:s', $log['timestamp']),
                $log['type'],
                $log['message'],
                $log['context'],
                $log['user_id'],
                $log['ip_address']
            ]);
        }
        
        fclose($fp);
        
        return [
            'status' => 'success',
            'file' => $filename
        ];
    }
    
    private function exportToJson($logs) {
        $filename = 'logs_' . date('Y-m-d_His') . '.json';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        file_put_contents($filepath, json_encode($logs, JSON_PRETTY_PRINT));
        
        return [
            'status' => 'success',
            'file' => $filename
        ];
    }
} 