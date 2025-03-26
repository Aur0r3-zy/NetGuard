<?php

namespace Core\Data;

class IntrusionRecord {
    private $db;
    private $logger;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function addRecord($data) {
        try {
            // 验证数据
            $validation = $this->validateRecord($data);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => '数据验证失败：' . implode(', ', $validation['errors'])
                ];
            }
            
            // 插入记录
            $query = "INSERT INTO intrusion_records (
                attack_time, source_ip, target_ip, attack_type, 
                status, description, severity, details
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['attack_time'],
                $data['source_ip'],
                $data['target_ip'],
                $data['attack_type'],
                $data['status'] ?? 'pending',
                $data['description'],
                $data['severity'],
                json_encode($data['details'] ?? [])
            ]);
            
            $recordId = $this->db->lastInsertId();
            
            // 记录日志
            $this->logger->info('新增入侵记录', [
                'record_id' => $recordId,
                'attack_type' => $data['attack_type'],
                'severity' => $data['severity']
            ]);
            
            return [
                'status' => 'success',
                'message' => '入侵记录添加成功',
                'record_id' => $recordId
            ];
        } catch (\Exception $e) {
            $this->logger->error('添加入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '添加入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRecords($filters = [], $limit = 100, $offset = 0) {
        try {
            $query = "SELECT * FROM intrusion_records WHERE 1=1";
            $params = [];
            
            // 应用过滤器
            if (!empty($filters['attack_type'])) {
                $query .= " AND attack_type = ?";
                $params[] = $filters['attack_type'];
            }
            
            if (!empty($filters['severity'])) {
                $query .= " AND severity = ?";
                $params[] = $filters['severity'];
            }
            
            if (!empty($filters['status'])) {
                $query .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['start_time'])) {
                $query .= " AND attack_time >= ?";
                $params[] = $filters['start_time'];
            }
            
            if (!empty($filters['end_time'])) {
                $query .= " AND attack_time <= ?";
                $params[] = $filters['end_time'];
            }
            
            if (!empty($filters['source_ip'])) {
                $query .= " AND source_ip = ?";
                $params[] = $filters['source_ip'];
            }
            
            if (!empty($filters['target_ip'])) {
                $query .= " AND target_ip = ?";
                $params[] = $filters['target_ip'];
            }
            
            // 添加排序和限制
            $query .= " ORDER BY attack_time DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            // 执行查询
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $records = [];
            while ($row = $stmt->fetch()) {
                $row['details'] = json_decode($row['details'], true);
                $records[] = $row;
            }
            
            return [
                'status' => 'success',
                'data' => $records
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function updateRecord($id, $data) {
        try {
            // 验证数据
            $validation = $this->validateRecord($data);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => '数据验证失败：' . implode(', ', $validation['errors'])
                ];
            }
            
            // 更新记录
            $query = "UPDATE intrusion_records SET 
                attack_time = ?, source_ip = ?, target_ip = ?, 
                attack_type = ?, status = ?, description = ?, 
                severity = ?, details = ?, updated_at = ?
                WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['attack_time'],
                $data['source_ip'],
                $data['target_ip'],
                $data['attack_type'],
                $data['status'],
                $data['description'],
                $data['severity'],
                json_encode($data['details'] ?? []),
                time(),
                $id
            ]);
            
            // 记录日志
            $this->logger->info('更新入侵记录', [
                'record_id' => $id,
                'attack_type' => $data['attack_type'],
                'severity' => $data['severity']
            ]);
            
            return [
                'status' => 'success',
                'message' => '入侵记录更新成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('更新入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '更新入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function deleteRecord($id) {
        try {
            $query = "DELETE FROM intrusion_records WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // 记录日志
            $this->logger->info('删除入侵记录', ['record_id' => $id]);
            
            return [
                'status' => 'success',
                'message' => '入侵记录删除成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('删除入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '删除入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function exportRecords($format = 'csv', $filters = []) {
        try {
            // 获取记录
            $records = $this->getRecords($filters);
            
            if ($records['status'] !== 'success') {
                throw new \Exception($records['message']);
            }
            
            // 根据格式导出
            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($records['data']);
                case 'json':
                    return $this->exportToJson($records['data']);
                default:
                    throw new \Exception('不支持的导出格式');
            }
        } catch (\Exception $e) {
            $this->logger->error('导出入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '导出入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    private function validateRecord($data) {
        $errors = [];
        
        // 验证必填字段
        $requiredFields = ['attack_time', 'source_ip', 'target_ip', 'attack_type', 'severity'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "{$field} 不能为空";
            }
        }
        
        // 验证IP地址格式
        if (!empty($data['source_ip']) && !filter_var($data['source_ip'], FILTER_VALIDATE_IP)) {
            $errors[] = '源IP地址格式无效';
        }
        
        if (!empty($data['target_ip']) && !filter_var($data['target_ip'], FILTER_VALIDATE_IP)) {
            $errors[] = '目标IP地址格式无效';
        }
        
        // 验证攻击类型
        $validAttackTypes = ['dos', 'ddos', 'sql_injection', 'xss', 'csrf', 'brute_force', 'other'];
        if (!empty($data['attack_type']) && !in_array($data['attack_type'], $validAttackTypes)) {
            $errors[] = '无效的攻击类型';
        }
        
        // 验证严重程度
        $validSeverities = ['low', 'medium', 'high', 'critical'];
        if (!empty($data['severity']) && !in_array($data['severity'], $validSeverities)) {
            $errors[] = '无效的严重程度';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function exportToCsv($records) {
        $filename = 'intrusion_records_' . date('Y-m-d_His') . '.csv';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        // 创建CSV文件
        $fp = fopen($filepath, 'w');
        
        // 写入表头
        fputcsv($fp, [
            'ID', '攻击时间', '源IP', '目标IP', '攻击类型',
            '状态', '描述', '严重程度', '详情'
        ]);
        
        // 写入数据
        foreach ($records as $record) {
            fputcsv($fp, [
                $record['id'],
                date('Y-m-d H:i:s', $record['attack_time']),
                $record['source_ip'],
                $record['target_ip'],
                $record['attack_type'],
                $record['status'],
                $record['description'],
                $record['severity'],
                json_encode($record['details'])
            ]);
        }
        
        fclose($fp);
        
        return [
            'status' => 'success',
            'file' => $filename
        ];
    }
    
    private function exportToJson($records) {
        $filename = 'intrusion_records_' . date('Y-m-d_His') . '.json';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        file_put_contents($filepath, json_encode($records, JSON_PRETTY_PRINT));
        
        return [
            'status' => 'success',
            'file' => $filename
        ];
    }
} 