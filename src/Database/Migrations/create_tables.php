<?php

namespace Database\Migrations;

class CreateTables {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        try {
            // 创建流量数据表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS traffic_data (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    source_ip VARCHAR(45) NOT NULL,
                    destination_ip VARCHAR(45) NOT NULL,
                    protocol VARCHAR(10) NOT NULL,
                    port INT UNSIGNED NOT NULL,
                    size INT UNSIGNED NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_source_ip (source_ip),
                    INDEX idx_destination_ip (destination_ip)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建基线数据表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS baseline_data (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    packet_count INT UNSIGNED NOT NULL,
                    byte_count BIGINT UNSIGNED NOT NULL,
                    protocols JSON NOT NULL,
                    ports JSON NOT NULL,
                    ip_addresses JSON NOT NULL,
                    avg_packet_size FLOAT NOT NULL,
                    packets_per_second FLOAT NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建监控数据表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS monitoring_data (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    current_traffic JSON NOT NULL,
                    anomalies JSON NOT NULL,
                    alerts JSON NOT NULL,
                    metrics JSON NOT NULL,
                    patterns JSON NOT NULL,
                    risk_assessment JSON NOT NULL,
                    recommendations JSON NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建告警记录表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS alerts (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    level VARCHAR(20) NOT NULL,
                    message TEXT NOT NULL,
                    details JSON NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建黑名单表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS blacklist (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip_range VARCHAR(45) NOT NULL,
                    reason TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at INT UNSIGNED NOT NULL,
                    updated_at INT UNSIGNED NOT NULL,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建白名单表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS whitelist (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip_range VARCHAR(45) NOT NULL,
                    reason TEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at INT UNSIGNED NOT NULL,
                    updated_at INT UNSIGNED NOT NULL,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建入侵记录表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS intrusion_records (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    attack_type VARCHAR(50) NOT NULL,
                    source_ip VARCHAR(45) NOT NULL,
                    destination_ip VARCHAR(45) NOT NULL,
                    severity VARCHAR(20) NOT NULL,
                    details JSON NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_source_ip (source_ip),
                    INDEX idx_destination_ip (destination_ip)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建攻击模式表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS attack_patterns (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    pattern_name VARCHAR(100) NOT NULL,
                    pattern_type VARCHAR(50) NOT NULL,
                    pattern_data JSON NOT NULL,
                    severity VARCHAR(20) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at INT UNSIGNED NOT NULL,
                    updated_at INT UNSIGNED NOT NULL,
                    INDEX idx_pattern_type (pattern_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // 创建风险评估表
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS risk_assessments (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    risk_level VARCHAR(20) NOT NULL,
                    risk_score INT UNSIGNED NOT NULL,
                    factors JSON NOT NULL,
                    recommendations JSON NOT NULL,
                    timestamp INT UNSIGNED NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_risk_level (risk_level)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            return true;
        } catch (\Exception $e) {
            error_log("创建数据库表失败：" . $e->getMessage());
            return false;
        }
    }
    
    public function down() {
        try {
            $tables = [
                'traffic_data',
                'baseline_data',
                'monitoring_data',
                'alerts',
                'blacklist',
                'whitelist',
                'intrusion_records',
                'attack_patterns',
                'risk_assessments'
            ];
            
            foreach ($tables as $table) {
                $this->db->exec("DROP TABLE IF EXISTS {$table}");
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("删除数据库表失败：" . $e->getMessage());
            return false;
        }
    }
} 