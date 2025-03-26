<?php
namespace App\Core\Data;

class TrafficMonitor
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

    public function getAnomalyCount()
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM traffic_anomalies 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    public function getTrafficData()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_packets,
                SUM(packet_size) as total_bytes,
                COUNT(DISTINCT source_ip) as unique_sources,
                COUNT(DISTINCT destination_ip) as unique_destinations
            FROM traffic_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAnomalies($limit = 100)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM traffic_anomalies
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
} 