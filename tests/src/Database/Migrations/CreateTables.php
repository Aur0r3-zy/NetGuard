<?php

namespace Database\Migrations;

class CreateTables {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        // 入侵记录表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS intrusion_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source_ip VARCHAR(45) NOT NULL,
                target_ip VARCHAR(45) NOT NULL,
                attack_type VARCHAR(50) NOT NULL,
                severity ENUM('low', 'medium', 'high') NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_source_ip (source_ip),
                INDEX idx_target_ip (target_ip),
                INDEX idx_attack_type (attack_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 流量日志表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS traffic_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source_ip VARCHAR(45) NOT NULL,
                destination_ip VARCHAR(45) NOT NULL,
                protocol VARCHAR(10) NOT NULL,
                port INT NOT NULL,
                packet_size INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_source_ip (source_ip),
                INDEX idx_destination_ip (destination_ip),
                INDEX idx_protocol (protocol),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 流量异常表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS traffic_anomalies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                source_ip VARCHAR(45) NOT NULL,
                description TEXT,
                severity ENUM('low', 'medium', 'high') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_source_ip (source_ip),
                INDEX idx_severity (severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 安全事件表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS security_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                severity ENUM('low', 'medium', 'high') NOT NULL,
                description TEXT,
                source VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_severity (severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 每日统计表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS daily_statistics (
                date DATE PRIMARY KEY,
                attack_count INT DEFAULT 0,
                anomaly_count INT DEFAULT 0,
                event_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 入侵标签表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS intrusion_tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                color VARCHAR(7) DEFAULT '#000000',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 入侵记录标签关联表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS intrusion_record_tags (
                record_id INT NOT NULL,
                tag_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (record_id, tag_id),
                FOREIGN KEY (record_id) REFERENCES intrusion_records(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES intrusion_tags(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 入侵记录评论表
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS intrusion_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                record_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (record_id) REFERENCES intrusion_records(id) ON DELETE CASCADE,
                INDEX idx_record_id (record_id),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    public function down() {
        // 按照创建顺序的反序删除表
        $tables = [
            'intrusion_comments',
            'intrusion_record_tags',
            'intrusion_tags',
            'daily_statistics',
            'security_events',
            'traffic_anomalies',
            'traffic_logs',
            'intrusion_records'
        ];
        
        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
} 