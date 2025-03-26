<?php

namespace Database\Seeds;

class InitData {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function run() {
        // 初始化入侵标签
        $this->initIntrusionTags();
        
        // 初始化每日统计数据
        $this->initDailyStatistics();
    }
    
    private function initIntrusionTags() {
        $tags = [
            ['name' => 'SQL注入', 'color' => '#FF0000'],
            ['name' => 'XSS攻击', 'color' => '#FF6600'],
            ['name' => 'CSRF攻击', 'color' => '#FFCC00'],
            ['name' => '文件上传', 'color' => '#00FF00'],
            ['name' => '命令注入', 'color' => '#0000FF'],
            ['name' => '目录遍历', 'color' => '#6600FF'],
            ['name' => '暴力破解', 'color' => '#FF00FF'],
            ['name' => '扫描探测', 'color' => '#00FFFF']
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO intrusion_tags (name, color)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE color = VALUES(color)
        ");
        
        foreach ($tags as $tag) {
            $stmt->execute([$tag['name'], $tag['color']]);
        }
    }
    
    private function initDailyStatistics() {
        // 初始化最近30天的统计数据
        $stmt = $this->db->prepare("
            INSERT INTO daily_statistics (date, attack_count, anomaly_count, event_count)
            VALUES (?, 0, 0, 0)
            ON DUPLICATE KEY UPDATE
                attack_count = VALUES(attack_count),
                anomaly_count = VALUES(anomaly_count),
                event_count = VALUES(event_count)
        ");
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stmt->execute([$date]);
        }
    }
} 