<?php

namespace Core\Monitor;

class AttackMonitor {
    private $trafficAnalyzer;
    private $algorithm;
    private $logger;
    
    public function __construct($trafficAnalyzer, $algorithm, $logger) {
        $this->trafficAnalyzer = $trafficAnalyzer;
        $this->algorithm = $algorithm;
        $this->logger = $logger;
    }
    
    public function monitorTraffic() {
        try {
            // 实时流量监测
            $packets = $this->trafficAnalyzer->capturePackets();
            
            // 异常行为识别
            $anomalies = $this->detectAnomalies($packets);
            
            // 数据包分析
            $analysis = $this->analyzePackets($packets);
            
            // 攻击源定位
            $sources = $this->locateAttackSources($packets);
            
            return [
                'status' => 'success',
                'data' => [
                    'traffic' => $packets,
                    'anomalies' => $anomalies,
                    'analysis' => $analysis,
                    'sources' => $sources
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量监测失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量监测失败：' . $e->getMessage()
            ];
        }
    }
    
    private function detectAnomalies($packets) {
        $anomalies = [];
        
        // 检测异常流量模式
        foreach ($packets as $packet) {
            if ($this->isAnomalous($packet)) {
                $anomalies[] = [
                    'type' => 'traffic_anomaly',
                    'packet' => $packet,
                    'timestamp' => time()
                ];
            }
        }
        
        return $anomalies;
    }
    
    private function analyzePackets($packets) {
        $analysis = [
            'total_packets' => count($packets),
            'protocols' => [],
            'sizes' => [],
            'timestamps' => []
        ];
        
        foreach ($packets as $packet) {
            // 协议统计
            $protocol = $packet['protocol'] ?? 'unknown';
            $analysis['protocols'][$protocol] = ($analysis['protocols'][$protocol] ?? 0) + 1;
            
            // 数据包大小统计
            $size = $packet['size'] ?? 0;
            $analysis['sizes'][] = $size;
            
            // 时间戳记录
            $analysis['timestamps'][] = $packet['timestamp'] ?? time();
        }
        
        return $analysis;
    }
    
    private function locateAttackSources($packets) {
        $sources = [];
        
        foreach ($packets as $packet) {
            if (isset($packet['source_ip'])) {
                $sources[$packet['source_ip']] = [
                    'count' => ($sources[$packet['source_ip']]['count'] ?? 0) + 1,
                    'last_seen' => time(),
                    'protocols' => array_unique(array_merge(
                        $sources[$packet['source_ip']]['protocols'] ?? [],
                        [$packet['protocol'] ?? 'unknown']
                    ))
                ];
            }
        }
        
        return $sources;
    }
    
    private function isAnomalous($packet) {
        // 实现异常检测逻辑
        $threshold = 1000; // 示例阈值
        
        return isset($packet['size']) && $packet['size'] > $threshold;
    }
} 