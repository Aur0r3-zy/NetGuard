<?php

namespace Core\Monitor;

class TrafficMonitor {
    private $db;
    private $logger;
    private $algorithm;
    private $baseline;
    
    public function __construct($db, $logger, $algorithm) {
        $this->db = $db;
        $this->logger = $logger;
        $this->algorithm = $algorithm;
        $this->baseline = [];
    }
    
    public function establishBaseline($duration = 3600) {
        try {
            // 收集历史流量数据
            $historicalData = $this->collectHistoricalData($duration);
            
            // 计算流量基线
            $this->baseline = $this->calculateBaseline($historicalData);
            
            return [
                'status' => 'success',
                'message' => '流量基线建立成功',
                'baseline' => $this->baseline
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量基线建立失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量基线建立失败：' . $e->getMessage()
            ];
        }
    }
    
    public function monitorTraffic() {
        try {
            // 获取实时流量数据
            $currentTraffic = $this->getCurrentTraffic();
            
            // 检测异常流量
            $anomalies = $this->detectAnomalies($currentTraffic);
            
            // 生成告警
            $alerts = $this->generateAlerts($anomalies);
            
            // 生成统计指标
            $metrics = $this->generateMetrics($currentTraffic);
            
            return [
                'status' => 'success',
                'data' => [
                    'current_traffic' => $currentTraffic,
                    'anomalies' => $anomalies,
                    'alerts' => $alerts,
                    'metrics' => $metrics
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量监控失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量监控失败：' . $e->getMessage()
            ];
        }
    }
    
    private function collectHistoricalData($duration) {
        $data = [];
        
        // 从数据库获取历史数据
        $query = "SELECT * FROM traffic_data WHERE timestamp >= ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([time() - $duration]);
        
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    private function calculateBaseline($historicalData) {
        $baseline = [
            'packet_count' => 0,
            'byte_count' => 0,
            'protocols' => [],
            'ports' => [],
            'ip_addresses' => []
        ];
        
        foreach ($historicalData as $data) {
            // 计算数据包数量
            $baseline['packet_count']++;
            
            // 计算字节数
            $baseline['byte_count'] += $data['size'] ?? 0;
            
            // 统计协议分布
            $protocol = $data['protocol'] ?? 'unknown';
            $baseline['protocols'][$protocol] = ($baseline['protocols'][$protocol] ?? 0) + 1;
            
            // 统计端口分布
            $port = $data['port'] ?? 0;
            $baseline['ports'][$port] = ($baseline['ports'][$port] ?? 0) + 1;
            
            // 统计IP地址分布
            $ip = $data['source_ip'] ?? '';
            if ($ip) {
                $baseline['ip_addresses'][$ip] = ($baseline['ip_addresses'][$ip] ?? 0) + 1;
            }
        }
        
        // 计算平均值
        $count = count($historicalData);
        if ($count > 0) {
            $baseline['avg_packet_size'] = $baseline['byte_count'] / $count;
            $baseline['packets_per_second'] = $baseline['packet_count'] / ($duration ?? 3600);
        }
        
        return $baseline;
    }
    
    private function getCurrentTraffic() {
        // 实现获取当前流量数据的逻辑
        return [];
    }
    
    private function detectAnomalies($currentTraffic) {
        $anomalies = [];
        
        // 检查数据包数量异常
        if ($currentTraffic['packet_count'] > $this->baseline['packet_count'] * 2) {
            $anomalies[] = [
                'type' => 'packet_count',
                'severity' => 'high',
                'description' => '数据包数量显著增加'
            ];
        }
        
        // 检查字节数异常
        if ($currentTraffic['byte_count'] > $this->baseline['byte_count'] * 2) {
            $anomalies[] = [
                'type' => 'byte_count',
                'severity' => 'high',
                'description' => '流量字节数显著增加'
            ];
        }
        
        // 检查协议分布异常
        foreach ($currentTraffic['protocols'] as $protocol => $count) {
            $baselineCount = $this->baseline['protocols'][$protocol] ?? 0;
            if ($count > $baselineCount * 3) {
                $anomalies[] = [
                    'type' => 'protocol_distribution',
                    'severity' => 'medium',
                    'description' => "协议 {$protocol} 的使用量异常增加"
                ];
            }
        }
        
        return $anomalies;
    }
    
    private function generateAlerts($anomalies) {
        $alerts = [];
        
        foreach ($anomalies as $anomaly) {
            if ($anomaly['severity'] === 'high') {
                $alerts[] = [
                    'level' => 'critical',
                    'message' => $anomaly['description'],
                    'timestamp' => time()
                ];
            } elseif ($anomaly['severity'] === 'medium') {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => $anomaly['description'],
                    'timestamp' => time()
                ];
            }
        }
        
        return $alerts;
    }
    
    private function generateMetrics($currentTraffic) {
        return [
            'timestamp' => time(),
            'packet_count' => $currentTraffic['packet_count'] ?? 0,
            'byte_count' => $currentTraffic['byte_count'] ?? 0,
            'protocols' => $currentTraffic['protocols'] ?? [],
            'ports' => $currentTraffic['ports'] ?? [],
            'ip_addresses' => $currentTraffic['ip_addresses'] ?? [],
            'avg_packet_size' => $currentTraffic['byte_count'] / ($currentTraffic['packet_count'] ?? 1),
            'packets_per_second' => ($currentTraffic['packet_count'] ?? 0) / 60
        ];
    }
} 