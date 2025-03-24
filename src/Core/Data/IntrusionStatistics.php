<?php

namespace Core\Data;

class IntrusionStatistics {
    private $db;
    private $logger;
    
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    public function updateStatistics($date = null) {
        try {
            if ($date === null) {
                $date = date('Y-m-d');
            }
            
            // 获取指定日期的入侵记录
            $query = "SELECT 
                attack_type, severity, COUNT(*) as count
                FROM intrusion_records
                WHERE DATE(FROM_UNIXTIME(attack_time)) = ?
                GROUP BY attack_type, severity";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$date]);
            $statistics = $stmt->fetchAll();
            
            // 更新统计表
            $this->db->beginTransaction();
            
            foreach ($statistics as $stat) {
                $query = "INSERT INTO intrusion_statistics 
                    (date, attack_type, severity, count)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $date,
                    $stat['attack_type'],
                    $stat['severity'],
                    $stat['count'],
                    $stat['count']
                ]);
            }
            
            $this->db->commit();
            
            // 记录日志
            $this->logger->info('更新入侵记录统计', [
                'date' => $date,
                'count' => count($statistics)
            ]);
            
            return [
                'status' => 'success',
                'message' => '统计更新成功',
                'data' => $statistics
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('更新入侵记录统计失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '更新入侵记录统计失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getStatistics($startDate = null, $endDate = null, $attackType = null, $severity = null) {
        try {
            $query = "SELECT 
                date, attack_type, severity, count
                FROM intrusion_statistics
                WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $query .= " AND date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $query .= " AND date <= ?";
                $params[] = $endDate;
            }
            
            if ($attackType) {
                $query .= " AND attack_type = ?";
                $params[] = $attackType;
            }
            
            if ($severity) {
                $query .= " AND severity = ?";
                $params[] = $severity;
            }
            
            $query .= " ORDER BY date DESC, attack_type, severity";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录统计失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录统计失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getSummary($startDate = null, $endDate = null) {
        try {
            $query = "SELECT 
                COUNT(DISTINCT date) as total_days,
                SUM(count) as total_attacks,
                COUNT(DISTINCT attack_type) as attack_types,
                COUNT(DISTINCT severity) as severity_levels
                FROM intrusion_statistics
                WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $query .= " AND date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $query .= " AND date <= ?";
                $params[] = $endDate;
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $summary = $stmt->fetch();
            
            // 获取攻击类型分布
            $query = "SELECT 
                attack_type, SUM(count) as total_count
                FROM intrusion_statistics
                WHERE 1=1";
            if ($startDate) {
                $query .= " AND date >= ?";
                $params[] = $startDate;
            }
            if ($endDate) {
                $query .= " AND date <= ?";
                $params[] = $endDate;
            }
            $query .= " GROUP BY attack_type ORDER BY total_count DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $summary['attack_type_distribution'] = $stmt->fetchAll();
            
            // 获取严重程度分布
            $query = "SELECT 
                severity, SUM(count) as total_count
                FROM intrusion_statistics
                WHERE 1=1";
            if ($startDate) {
                $query .= " AND date >= ?";
                $params[] = $startDate;
            }
            if ($endDate) {
                $query .= " AND date <= ?";
                $params[] = $endDate;
            }
            $query .= " GROUP BY severity ORDER BY total_count DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $summary['severity_distribution'] = $stmt->fetchAll();
            
            return [
                'status' => 'success',
                'data' => $summary
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录统计摘要失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录统计摘要失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getTrends($startDate = null, $endDate = null, $interval = 'day') {
        try {
            $query = "SELECT 
                date, SUM(count) as total_count
                FROM intrusion_statistics
                WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $query .= " AND date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $query .= " AND date <= ?";
                $params[] = $endDate;
            }
            
            switch ($interval) {
                case 'week':
                    $query .= " GROUP BY YEARWEEK(date) ORDER BY date";
                    break;
                case 'month':
                    $query .= " GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY date";
                    break;
                default:
                    $query .= " GROUP BY date ORDER BY date";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return [
                'status' => 'success',
                'data' => $stmt->fetchAll()
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取入侵记录趋势失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取入侵记录趋势失败：' . $e->getMessage()
            ];
        }
    }
} 