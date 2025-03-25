<?php

namespace Core\Log;

class LogManager {
    private $db;
    private $logger;
    private $logLevel;
    private $logPath;
    private $maxFileSize;
    private $maxFiles;
    private $logFormat;
    private $logRotation;
    private $logCompression;
    private $logRetention;
    private $logFilters;
    
    public function __construct(
        $db, 
        $logger, 
        $logLevel = 'info',
        $logPath = null,
        $maxFileSize = 10485760, // 10MB
        $maxFiles = 10,
        $logFormat = 'json',
        $logRotation = true,
        $logCompression = false,
        $logRetention = 30 // 30天
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->logPath = $logPath ?? __DIR__ . '/../../logs/';
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        $this->logFormat = $logFormat;
        $this->logRotation = $logRotation;
        $this->logCompression = $logCompression;
        $this->logRetention = $logRetention;
        $this->logFilters = [];
        
        // 确保日志目录存在
        $this->ensureLogDirectory();
    }
    
    public function logMessage($message, $type = 'info', $context = []) {
        try {
            // 验证日志级别
            if (!$this->shouldLog($type)) {
                return false;
            }
            
            // 应用日志过滤器
            if (!$this->applyFilters($message, $type, $context)) {
                return false;
            }
            
            // 准备日志数据
            $logData = $this->prepareLogData($message, $type, $context);
            
            // 保存到数据库
            $this->saveToDatabase($logData);
            
            // 写入文件日志
            $this->writeToFile($logData);
            
            // 检查是否需要日志轮转
            if ($this->logRotation) {
                $this->checkLogRotation();
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('日志记录失败：' . $e->getMessage());
            return false;
        }
    }
    
    private function prepareLogData($message, $type, $context) {
        return [
            'message' => $message,
            'type' => $type,
            'context' => json_encode($context),
            'timestamp' => time(),
            'user_id' => $context['user_id'] ?? null,
            'ip_address' => $context['ip_address'] ?? null,
            'request_id' => $context['request_id'] ?? $this->generateRequestId(),
            'session_id' => $context['session_id'] ?? session_id(),
            'user_agent' => $context['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_method' => $context['request_method'] ?? $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $context['request_uri'] ?? $_SERVER['REQUEST_URI'] ?? null,
            'server_name' => $context['server_name'] ?? $_SERVER['SERVER_NAME'] ?? null,
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true) - ($context['start_time'] ?? microtime(true))
        ];
    }
    
    private function generateRequestId() {
        return uniqid('req_', true);
    }
    
    private function ensureLogDirectory() {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    private function applyFilters($message, $type, $context) {
        foreach ($this->logFilters as $filter) {
            if (!$filter($message, $type, $context)) {
                return false;
            }
        }
        return true;
    }
    
    public function addFilter($filter) {
        $this->logFilters[] = $filter;
    }
    
    private function checkLogRotation() {
        $logFile = $this->logPath . 'system.log';
        
        if (file_exists($logFile) && filesize($logFile) >= $this->maxFileSize) {
            $this->rotateLogFile($logFile);
        }
    }
    
    private function rotateLogFile($logFile) {
        $info = pathinfo($logFile);
        $rotatedFile = $info['dirname'] . '/' . $info['filename'] . '_' . date('Y-m-d_His') . '.' . $info['extension'];
        
        // 重命名当前日志文件
        rename($logFile, $rotatedFile);
        
        // 如果启用压缩，压缩轮转的日志文件
        if ($this->logCompression) {
            $this->compressLogFile($rotatedFile);
        }
        
        // 清理旧的日志文件
        $this->cleanupOldLogs();
    }
    
    private function compressLogFile($file) {
        $gzFile = $file . '.gz';
        $fp = fopen($file, 'rb');
        $gzfp = gzopen($gzFile, 'wb');
        
        while (!feof($fp)) {
            gzwrite($gzfp, fread($fp, 8192));
        }
        
        fclose($fp);
        gzclose($gzfp);
        
        // 删除原始文件
        unlink($file);
    }
    
    private function cleanupOldLogs() {
        $files = glob($this->logPath . 'system.log_*');
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // 保留最新的N个文件
        $files = array_slice($files, $this->maxFiles);
        
        // 删除旧文件
        foreach ($files as $file) {
            unlink($file);
        }
        
        // 清理过期的数据库日志
        $this->cleanupDatabaseLogs();
    }
    
    private function cleanupDatabaseLogs() {
        try {
            $query = "DELETE FROM system_logs WHERE timestamp < ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([time() - ($this->logRetention * 24 * 60 * 60)]);
        } catch (\Exception $e) {
            $this->logger->error('清理数据库日志失败：' . $e->getMessage());
        }
    }
    
    private function writeToFile($logData) {
        $logEntry = $this->formatLogEntry($logData);
        $logFile = $this->logPath . 'system.log';
        
        file_put_contents($logFile, $logEntry . "\n", FILE_APPEND);
    }
    
    private function formatLogEntry($logData) {
        switch ($this->logFormat) {
            case 'json':
                return json_encode($logData, JSON_UNESCAPED_UNICODE);
            case 'text':
                return sprintf(
                    "[%s] %s: %s %s",
                    date('Y-m-d H:i:s', $logData['timestamp']),
                    strtoupper($logData['type']),
                    $logData['message'],
                    !empty($logData['context']) ? json_encode($logData['context']) : ''
                );
            default:
                return json_encode($logData, JSON_UNESCAPED_UNICODE);
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
            
            if (!empty($filters['ip_address'])) {
                $query .= " AND ip_address = ?";
                $params[] = $filters['ip_address'];
            }
            
            if (!empty($filters['request_id'])) {
                $query .= " AND request_id = ?";
                $params[] = $filters['request_id'];
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
                'data' => $logs,
                'total' => $this->getTotalLogs($filters),
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取日志失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取日志失败：' . $e->getMessage()
            ];
        }
    }
    
    private function getTotalLogs($filters) {
        $query = "SELECT COUNT(*) as total FROM system_logs WHERE 1=1";
        $params = [];
        
        // 应用相同的过滤器
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
        
        if (!empty($filters['ip_address'])) {
            $query .= " AND ip_address = ?";
            $params[] = $filters['ip_address'];
        }
        
        if (!empty($filters['request_id'])) {
            $query .= " AND request_id = ?";
            $params[] = $filters['request_id'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'];
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
            
            // 清理文件日志
            $this->cleanupFileLogs($before);
            
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
    
    private function cleanupFileLogs($before) {
        $files = glob($this->logPath . 'system.log*');
        $beforeTimestamp = $before ? strtotime($before) : null;
        
        foreach ($files as $file) {
            if ($beforeTimestamp === null || filemtime($file) < $beforeTimestamp) {
                unlink($file);
            }
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
                case 'excel':
                    return $this->exportToExcel($logs['data']);
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
    
    private function exportToExcel($logs) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $headers = ['时间', '类型', '消息', '上下文', '用户ID', 'IP地址', '请求ID', '会话ID', '用户代理', '请求方法', '请求URI', '服务器名称', '内存使用', '执行时间'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        
        // 写入数据
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValueByColumnAndRow(1, $row, date('Y-m-d H:i:s', $log['timestamp']));
            $sheet->setCellValueByColumnAndRow(2, $row, $log['type']);
            $sheet->setCellValueByColumnAndRow(3, $row, $log['message']);
            $sheet->setCellValueByColumnAndRow(4, $row, json_encode($log['context']));
            $sheet->setCellValueByColumnAndRow(5, $row, $log['user_id']);
            $sheet->setCellValueByColumnAndRow(6, $row, $log['ip_address']);
            $sheet->setCellValueByColumnAndRow(7, $row, $log['request_id']);
            $sheet->setCellValueByColumnAndRow(8, $row, $log['session_id']);
            $sheet->setCellValueByColumnAndRow(9, $row, $log['user_agent']);
            $sheet->setCellValueByColumnAndRow(10, $row, $log['request_method']);
            $sheet->setCellValueByColumnAndRow(11, $row, $log['request_uri']);
            $sheet->setCellValueByColumnAndRow(12, $row, $log['server_name']);
            $sheet->setCellValueByColumnAndRow(13, $row, $log['memory_usage']);
            $sheet->setCellValueByColumnAndRow(14, $row, $log['execution_time']);
            $row++;
        }
        
        // 保存文件
        $filename = 'logs_' . date('Y-m-d_His') . '.xlsx';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return [
            'status' => 'success',
            'file' => $filename
        ];
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
        $query = "INSERT INTO system_logs (
            message, type, context, timestamp, user_id, ip_address,
            request_id, session_id, user_agent, request_method,
            request_uri, server_name, memory_usage, execution_time
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $logData['message'],
            $logData['type'],
            $logData['context'],
            $logData['timestamp'],
            $logData['user_id'],
            $logData['ip_address'],
            $logData['request_id'],
            $logData['session_id'],
            $logData['user_agent'],
            $logData['request_method'],
            $logData['request_uri'],
            $logData['server_name'],
            $logData['memory_usage'],
            $logData['execution_time']
        ]);
    }
}