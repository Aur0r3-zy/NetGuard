<?php

namespace Database\Seeds;

class InitData {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function run() {
        try {
            // 初始化攻击模式数据
            $this->initAttackPatterns();
            
            // 初始化黑名单数据
            $this->initBlacklist();
            
            // 初始化白名单数据
            $this->initWhitelist();
            
            return true;
        } catch (\Exception $e) {
            error_log("初始化数据失败：" . $e->getMessage());
            return false;
        }
    }
    
    private function initAttackPatterns() {
        $patterns = [
            [
                'pattern_name' => '端口扫描',
                'pattern_type' => 'scan',
                'pattern_data' => json_encode([
                    'threshold' => 100,
                    'time_window' => 60,
                    'port_range' => [1, 65535]
                ]),
                'severity' => 'high',
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'pattern_name' => '暴力破解',
                'pattern_type' => 'brute_force',
                'pattern_data' => json_encode([
                    'threshold' => 5,
                    'time_window' => 300,
                    'max_attempts' => 10
                ]),
                'severity' => 'high',
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'pattern_name' => 'DDoS攻击',
                'pattern_type' => 'ddos',
                'pattern_data' => json_encode([
                    'threshold' => 1000,
                    'time_window' => 60,
                    'packet_size' => 1500
                ]),
                'severity' => 'critical',
                'created_at' => time(),
                'updated_at' => time()
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO attack_patterns (
                pattern_name, pattern_type, pattern_data,
                severity, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'active', ?, ?)
        ");
        
        foreach ($patterns as $pattern) {
            $stmt->execute([
                $pattern['pattern_name'],
                $pattern['pattern_type'],
                $pattern['pattern_data'],
                $pattern['severity'],
                $pattern['created_at'],
                $pattern['updated_at']
            ]);
        }
    }
    
    private function initBlacklist() {
        $blacklist = [
            [
                'ip_range' => '192.168.1.0/24',
                'reason' => '内部测试网络',
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'ip_range' => '10.0.0.0/8',
                'reason' => '内部网络',
                'created_at' => time(),
                'updated_at' => time()
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO blacklist (
                ip_range, reason, status, created_at, updated_at
            ) VALUES (?, ?, 'active', ?, ?)
        ");
        
        foreach ($blacklist as $item) {
            $stmt->execute([
                $item['ip_range'],
                $item['reason'],
                $item['created_at'],
                $item['updated_at']
            ]);
        }
    }
    
    private function initWhitelist() {
        $whitelist = [
            [
                'ip_range' => '127.0.0.1/32',
                'reason' => '本地回环地址',
                'created_at' => time(),
                'updated_at' => time()
            ],
            [
                'ip_range' => '::1/128',
                'reason' => 'IPv6本地回环地址',
                'created_at' => time(),
                'updated_at' => time()
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO whitelist (
                ip_range, reason, status, created_at, updated_at
            ) VALUES (?, ?, 'active', ?, ?)
        ");
        
        foreach ($whitelist as $item) {
            $stmt->execute([
                $item['ip_range'],
                $item['reason'],
                $item['created_at'],
                $item['updated_at']
            ]);
        }
    }
} 