<?php

namespace Core\Data;

class IntrusionComments {
    private $db;
    private $logger;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function addComment($recordId, $userId, $content) {
        try {
            // 验证评论内容
            if (!$this->validateComment($content)) {
                return [
                    'status' => 'error',
                    'message' => '评论内容无效'
                ];
            }
            
            // 添加评论
            $query = "INSERT INTO intrusion_comments (record_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId, $userId, $content]);
            
            $commentId = $this->db->lastInsertId();
            
            // 记录日志
            $this->logger->info('添加入侵记录评论', [
                'record_id' => $recordId,
                'user_id' => $userId,
                'comment_id' => $commentId
            ]);
            
            return [
                'status' => 'success',
                'message' => '评论添加成功',
                'comment_id' => $commentId
            ];
        } catch (\Exception $e) {
            $this->logger->error('添加入侵记录评论失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '添加入侵记录评论失败：' . $e->getMessage()
            ];
        }
    }
    
    public function updateComment($commentId, $userId, $content) {
        try {
            // 验证评论内容
            if (!$this->validateComment($content)) {
                return [
                    'status' => 'error',
                    'message' => '评论内容无效'
                ];
            }
            
            // 检查评论是否存在且属于当前用户
            $query = "SELECT id FROM intrusion_comments WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$commentId, $userId]);
            
            if (!$stmt->fetch()) {
                return [
                    'status' => 'error',
                    'message' => '评论不存在或无权限修改'
                ];
            }
            
            // 更新评论
            $query = "UPDATE intrusion_comments SET content = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$content, $commentId, $userId]);
            
            // 记录日志
            $this->logger->info('更新入侵记录评论', [
                'comment_id' => $commentId,
                'user_id' => $userId
            ]);
            
            return [
                'status' => 'success',
                'message' => '评论更新成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('更新入侵记录评论失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '更新入侵记录评论失败：' . $e->getMessage()
            ];
        }
    }
    
    public function deleteComment($commentId, $userId) {
        try {
            // 检查评论是否存在且属于当前用户
            $query = "SELECT id FROM intrusion_comments WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$commentId, $userId]);
            
            if (!$stmt->fetch()) {
                return [
                    'status' => 'error',
                    'message' => '评论不存在或无权限删除'
                ];
            }
            
            // 删除评论
            $query = "DELETE FROM intrusion_comments WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$commentId, $userId]);
            
            // 记录日志
            $this->logger->info('删除入侵记录评论', [
                'comment_id' => $commentId,
                'user_id' => $userId
            ]);
            
            return [
                'status' => 'success',
                'message' => '评论删除成功'
            ];
        } catch (\Exception $e) {
            $this->logger->error('删除入侵记录评论失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '删除入侵记录评论失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRecordComments($recordId, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT c.*, u.username 
                FROM intrusion_comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.record_id = ?
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$recordId, $limit, $offset]);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录评论失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录评论失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getUserComments($userId, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT c.*, r.attack_type, r.severity
                FROM intrusion_comments c
                LEFT JOIN intrusion_records r ON c.record_id = r.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $limit, $offset]);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取用户评论失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取用户评论失败：' . $e->getMessage()
            ];
        }
    }
    
    private function validateComment($content) {
        // 评论长度限制
        if (strlen($content) < 1 || strlen($content) > 1000) {
            return false;
        }
        
        // 评论内容过滤
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        return true;
    }
} 