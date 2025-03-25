<?php

namespace Controllers;

use Database\Database;

class ApiController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function resolveAlert($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE alerts 
                SET status = 'resolved', 
                    resolved_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            return json_encode([
                'success' => true,
                'message' => '告警已标记为已处理'
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getTrafficStats() {
        try {
            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_packets,
                    SUM(bytes) as total_bytes,
                    COUNT(DISTINCT source_ip) as unique_sources,
                    COUNT(DISTINCT destination_ip) as unique_destinations
                FROM traffic_data 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ")->fetch();
            
            return json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getAlertStats() {
        try {
            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_alerts,
                    SUM(CASE WHEN severity >= 8 THEN 1 ELSE 0 END) as critical_alerts,
                    SUM(CASE WHEN severity >= 5 AND severity < 8 THEN 1 ELSE 0 END) as warning_alerts,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_alerts
                FROM alerts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ")->fetch();
            
            return json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getAttackPatterns() {
        try {
            $patterns = $this->db->query("
                SELECT 
                    attack_type,
                    COUNT(*) as count,
                    AVG(severity) as avg_severity
                FROM alerts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY attack_type
                ORDER BY count DESC
                LIMIT 10
            ")->fetchAll();
            
            return json_encode([
                'success' => true,
                'data' => $patterns
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getTopAttackers() {
        try {
            $attackers = $this->db->query("
                SELECT 
                    source_ip,
                    COUNT(*) as attack_count,
                    MAX(severity) as max_severity,
                    GROUP_CONCAT(DISTINCT attack_type) as attack_types
                FROM alerts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY source_ip
                ORDER BY attack_count DESC
                LIMIT 10
            ")->fetchAll();
            
            return json_encode([
                'success' => true,
                'data' => $attackers
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getTrafficTrend() {
        try {
            $trend = $this->db->query("
                SELECT 
                    DATE_FORMAT(created_at, '%H:00') as hour,
                    COUNT(*) as packet_count,
                    SUM(bytes) as total_bytes
                FROM traffic_data 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY hour
                ORDER BY hour
            ")->fetchAll();
            
            return json_encode([
                'success' => true,
                'data' => $trend
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function getAlertTrend() {
        try {
            $trend = $this->db->query("
                SELECT 
                    DATE_FORMAT(created_at, '%H:00') as hour,
                    COUNT(*) as alert_count,
                    AVG(severity) as avg_severity
                FROM alerts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY hour
                ORDER BY hour
            ")->fetchAll();
            
            return json_encode([
                'success' => true,
                'data' => $trend
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
} 