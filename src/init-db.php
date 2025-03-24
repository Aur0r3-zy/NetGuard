<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Config\ConfigLoader;
use Utils\Database;
use Utils\Logger;

// 加载配置
$configLoader = new ConfigLoader();
$config = $configLoader->load();

// 初始化日志
$logger = new Logger(
    $config['logging']['file'],
    $config['logging']['max_size'],
    $config['logging']['backup_count'],
    $config['logging']['level']
);

try {
    // 连接数据库
    $db = new Database(
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['name'],
        $config['database']['user'],
        $config['database']['password']
    );
    $db->connect();
    
    // 创建攻击记录表
    $db->query("
        CREATE TABLE IF NOT EXISTS attack_records (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            source_ip VARCHAR(45) NOT NULL,
            target_ip VARCHAR(45) NOT NULL,
            attack_type VARCHAR(50) NOT NULL,
            confidence DECIMAL(5,2) NOT NULL,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_timestamp (timestamp),
            INDEX idx_source_ip (source_ip),
            INDEX idx_target_ip (target_ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 创建网络流量统计表
    $db->query("
        CREATE TABLE IF NOT EXISTS traffic_stats (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            protocol VARCHAR(10) NOT NULL,
            source_port INT UNSIGNED,
            target_port INT UNSIGNED,
            packet_count INT UNSIGNED NOT NULL,
            byte_count BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_timestamp (timestamp),
            INDEX idx_protocol (protocol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 创建检测结果表
    $db->query("
        CREATE TABLE IF NOT EXISTS detection_results (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            is_attack BOOLEAN NOT NULL,
            confidence DECIMAL(5,2) NOT NULL,
            features JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_timestamp (timestamp),
            INDEX idx_is_attack (is_attack)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $logger->info("数据库初始化成功");
    
} catch (\Exception $e) {
    $logger->critical("数据库初始化失败: " . $e->getMessage());
    exit(1);
} 