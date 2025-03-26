<?php

namespace Core\Data;

class IntrusionTags {
    private $db;
    private $logger;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function addTag($recordId, $tag) {
        try {
            // 验证标签格式
            if (!$this->validateTag($tag)) {
                return [
                    'status' => 'error',
                    'message' => '标签格式无效'
                ];
            }
            
            // 检查标签是否已存在
            $query = "SELECT id FROM intrusion_tags 
                WHERE record_id = ? AND tag = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId, $tag]);
            
            if ($stmt->fetch()) {
                return [
                    'status' => 'error',
                    'message' => '标签已存在'
                ];
            }
            
            // 添加标签
            $query = "INSERT INTO intrusion_tags (record_id, tag) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId, $tag]);
            
            // 记录日志
            $this->logger->info('添加入侵记录标签', [
                'record_id' => $recordId,
                'tag' => $tag
            ]);
            
            return [
                'status' => 'success',
                'message' => '标签添加成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('添加入侵记录标签失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '添加入侵记录标签失败：' . $e->getMessage()
            ];
        }
    }
    
    public function removeTag($recordId, $tag) {
        try {
            $query = "DELETE FROM intrusion_tags WHERE record_id = ? AND tag = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId, $tag]);
            
            // 记录日志
            $this->logger->info('删除入侵记录标签', [
                'record_id' => $recordId,
                'tag' => $tag
            ]);
            
            return [
                'status' => 'success',
                'message' => '标签删除成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('删除入侵记录标签失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '删除入侵记录标签失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRecordTags($recordId) {
        try {
            $query = "SELECT tag FROM intrusion_tags WHERE record_id = ? ORDER BY tag";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId]);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll(\PDO::FETCH_COLUMN)
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录标签失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录标签失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRecordsByTag($tag, $limit = 100, $offset = 0) {
        try {
            $query = "SELECT r.* FROM intrusion_records r
                INNER JOIN intrusion_tags t ON r.id = t.record_id
                WHERE t.tag = ?
                ORDER BY r.attack_time DESC
                LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tag, $limit, $offset]);
            
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
            $this->logger->error('获取标签相关入侵记录失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取标签相关入侵记录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getAllTags($limit = 100) {
        try {
            $query = "SELECT DISTINCT tag, COUNT(*) as count
                FROM intrusion_tags
                GROUP BY tag
                ORDER BY count DESC
                LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$limit]);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取所有标签失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取所有标签失败：' . $e->getMessage()
            ];
        }
    }
    
    public function searchTags($keyword, $limit = 10) {
        try {
            $query = "SELECT DISTINCT tag, COUNT(*) as count
                FROM intrusion_tags
                WHERE tag LIKE ?
                GROUP BY tag
                ORDER BY count DESC
                LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute(['%' . $keyword . '%', $limit]);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('搜索标签失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '搜索标签失败：' . $e->getMessage()
            ];
        }
    }
    
    private function validateTag($tag) {
        // 标签长度限制
        if (strlen($tag) < 2 || strlen($tag) > 50) {
            return false;
        }
        
        // 标签格式限制（只允许字母、数字、下划线和连字符）
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tag)) {
            return false;
        }
        
        return true;
    }
} 