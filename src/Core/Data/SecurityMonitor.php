<?php
namespace App\Core\Data;

class SecurityMonitor
{
    private $db;

    public function __construct()
    {
        $this->db = new \PDO(
            "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        );
    }

    public function getEventCount()
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM security_events 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    public function getSecurityData()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_events,
                COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
                COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
                COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity
            FROM security_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAnomalies($limit = 100)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM security_events
            WHERE severity IN ('high', 'medium')
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
} 