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
    
    /**
     * 获取总攻击次数
     * @return int
     */
    public function getTotalAttacks(): int {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM intrusion_records
            ");
            $stmt->execute();
            return (int)$stmt->fetch()['total'];
        } catch (\Exception $e) {
            $this->logger->error('获取总攻击次数失败：' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 获取今日攻击次数
     * @return int
     */
    public function getTodayAttacks(): int {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM intrusion_records 
                WHERE DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            return (int)$stmt->fetch()['count'];
        } catch (\Exception $e) {
            $this->logger->error('获取今日攻击次数失败：' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 获取最近的攻击记录
     * @param int $limit 限制返回数量
     * @return array
     */
    public function getRecentAttacks(int $limit = 10): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    attack_type,
                    source_ip,
                    target_ip,
                    severity,
                    description,
                    created_at
                FROM intrusion_records
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取最近攻击记录失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取攻击趋势
     * @param string $period 时间周期（hour/day/week/month）
     * @return array
     */
    public function getAttackTrend(string $period = 'day'): array {
        try {
            $query = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
                FROM intrusion_records
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 " . strtoupper($period) . ")
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取攻击趋势失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取攻击源分析
     * @return array
     */
    public function getAttackSources(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    source_ip,
                    COUNT(*) as attack_count,
                    COUNT(DISTINCT attack_type) as attack_types
                FROM intrusion_records
                GROUP BY source_ip
                ORDER BY attack_count DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取攻击源分析失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取攻击目标分析
     * @return array
     */
    public function getAttackTargets(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    target_ip,
                    COUNT(*) as attack_count,
                    COUNT(DISTINCT attack_type) as attack_types
                FROM intrusion_records
                GROUP BY target_ip
                ORDER BY attack_count DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取攻击目标分析失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取攻击严重程度分布
     * @return array
     */
    public function getAttackSeverityDistribution(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    severity,
                    COUNT(*) as count
                FROM intrusion_records
                GROUP BY severity
                ORDER BY count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取攻击严重程度分布失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取异常检测结果
     * @return array
     */
    public function getAnomalies(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    attack_type,
                    source_ip,
                    target_ip,
                    severity,
                    description,
                    created_at
                FROM intrusion_records
                WHERE severity IN ('high', 'critical')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取异常检测结果失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取当前风险等级
     * @return string
     */
    public function getCurrentRiskLevel(): string {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as critical_count
                FROM intrusion_records
                WHERE severity = 'critical'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $criticalCount = (int)$stmt->fetch()['critical_count'];
            
            if ($criticalCount >= 5) {
                return 'high';
            } elseif ($criticalCount >= 2) {
                return 'medium';
            }
            return 'low';
        } catch (\Exception $e) {
            $this->logger->error('获取当前风险等级失败：' . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * 获取安全建议
     * @return array
     */
    public function getSecurityRecommendations(): array {
        try {
            $riskLevel = $this->getCurrentRiskLevel();
            $recommendations = [];
            
            switch ($riskLevel) {
                case 'high':
                    $recommendations[] = '立即检查所有高风险攻击源';
                    $recommendations[] = '加强防火墙规则';
                    $recommendations[] = '更新安全补丁';
                    break;
                case 'medium':
                    $recommendations[] = '检查可疑IP地址';
                    $recommendations[] = '审查系统日志';
                    $recommendations[] = '更新安全策略';
                    break;
                case 'low':
                    $recommendations[] = '定期检查系统安全';
                    $recommendations[] = '保持安全更新';
                    $recommendations[] = '加强用户培训';
                    break;
            }
            
            return $recommendations;
        } catch (\Exception $e) {
            $this->logger->error('获取安全建议失败：' . $e->getMessage());
            return [];
        }
    }
} 