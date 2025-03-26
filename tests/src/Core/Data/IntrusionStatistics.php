<?php

namespace App\Core\Data;

class IntrusionStatistics {
    private $db;
    private $logger;
    
    public function __construct($logger) {
        $this->db = new \PDO(
            "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        );
        $this->logger = $logger;
    }
    
    public function getDailyCount($date) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM intrusion_records 
            WHERE DATE(created_at) = ?
        ");
        $stmt->execute([$date]);
        return $stmt->fetch()['count'];
    }
    
    public function getAttackTypeDistribution() {
        $stmt = $this->db->prepare("
            SELECT attack_type, COUNT(*) as count 
            FROM intrusion_records 
            GROUP BY attack_type
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStatistics($date) {
        $stmt = $this->db->prepare("
            INSERT INTO daily_statistics (date, attack_count)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE attack_count = VALUES(attack_count)
        ");
        
        $count = $this->getDailyCount($date);
        return $stmt->execute([$date, $count]);
    }
    
    public function getStatistics($startDate = null, $endDate = null) {
        $query = "SELECT * FROM daily_statistics WHERE 1=1";
        $params = [];
        
        if ($startDate) {
            $query .= " AND date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND date <= ?";
            $params[] = $endDate;
        }
        
        $query .= " ORDER BY date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
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