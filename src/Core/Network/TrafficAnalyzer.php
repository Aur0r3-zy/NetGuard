<?php

namespace App\Core\Network;

use App\Core\Logger\Logger;
use App\Data\FeatureExtractor;
use App\Data\Normalizer;

class TrafficAnalyzer {
    private $logger;
    private $featureExtractor;
    private $normalizer;
    private $db;
    private $config;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->featureExtractor = new FeatureExtractor();
        $this->normalizer = new Normalizer();
        $this->db = new \PDO(
            "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        );
        $this->config = require_once __DIR__ . '/../../config/network.php';
    }
    
    /**
     * 分析网络流量
     * @param array $trafficData 流量数据
     * @return array 分析结果
     */
    public function analyzeTraffic(array $trafficData): array {
        try {
            // 1. 数据验证
            if (!$this->validateTrafficData($trafficData)) {
                throw new \Exception('无效的流量数据格式');
            }
            
            // 2. 特征提取
            $features = $this->featureExtractor->extract($trafficData);
            
            // 3. 特征标准化
            $normalizedFeatures = $this->normalizer->normalize($features);
            
            // 4. 流量分析
            $analysis = [
                'bandwidth_usage' => $this->analyzeBandwidth($trafficData),
                'protocol_distribution' => $this->analyzeProtocols($trafficData),
                'top_ips' => $this->getTopIPs($trafficData),
                'anomalies' => $this->detectAnomalies($normalizedFeatures),
                'performance_metrics' => $this->calculatePerformanceMetrics($trafficData)
            ];
            
            // 5. 保存分析结果
            $this->saveAnalysis($analysis);
            
            return [
                'status' => 'success',
                'data' => $analysis
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量分析失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证流量数据
     * @param array $data
     * @return bool
     */
    private function validateTrafficData(array $data): bool {
        $requiredFields = [
            'timestamp',
            'source_ip',
            'destination_ip',
            'protocol',
            'bytes',
            'packets'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 分析带宽使用情况
     * @param array $trafficData
     * @return array
     */
    private function analyzeBandwidth(array $trafficData): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(bytes) as total_bytes,
                    AVG(bytes) as avg_bytes,
                    MAX(bytes) as max_bytes,
                    COUNT(*) as total_packets
                FROM traffic_data
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            $stats = $stmt->fetch();
            
            return [
                'total_bandwidth' => $stats['total_bytes'] ?? 0,
                'average_bandwidth' => $stats['avg_bytes'] ?? 0,
                'peak_bandwidth' => $stats['max_bytes'] ?? 0,
                'packet_count' => $stats['total_packets'] ?? 0,
                'bandwidth_trend' => $this->getBandwidthTrend()
            ];
        } catch (\Exception $e) {
            $this->logger->error('带宽分析失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 分析协议分布
     * @param array $trafficData
     * @return array
     */
    private function analyzeProtocols(array $trafficData): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    protocol,
                    COUNT(*) as packet_count,
                    SUM(bytes) as total_bytes
                FROM traffic_data
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY protocol
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('协议分析失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取流量最大的IP地址
     * @param array $trafficData
     * @return array
     */
    private function getTopIPs(array $trafficData): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    source_ip,
                    COUNT(*) as packet_count,
                    SUM(bytes) as total_bytes
                FROM traffic_data
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY source_ip
                ORDER BY total_bytes DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取Top IP失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 检测流量异常
     * @param array $features
     * @return array
     */
    private function detectAnomalies(array $features): array {
        $anomalies = [];
        
        try {
            // 1. 检查带宽突发
            if ($this->isBandwidthBurst($features)) {
                $anomalies[] = [
                    'type' => 'bandwidth_burst',
                    'severity' => 'high',
                    'description' => '检测到带宽突发'
                ];
            }
            
            // 2. 检查协议异常
            if ($this->isProtocolAnomaly($features)) {
                $anomalies[] = [
                    'type' => 'protocol_anomaly',
                    'severity' => 'medium',
                    'description' => '检测到协议使用异常'
                ];
            }
            
            // 3. 检查连接数异常
            if ($this->isConnectionAnomaly($features)) {
                $anomalies[] = [
                    'type' => 'connection_anomaly',
                    'severity' => 'high',
                    'description' => '检测到连接数异常'
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('异常检测失败：' . $e->getMessage());
        }
        
        return $anomalies;
    }
    
    /**
     * 计算性能指标
     * @param array $trafficData
     * @return array
     */
    private function calculatePerformanceMetrics(array $trafficData): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    AVG(packet_loss) as avg_packet_loss,
                    AVG(latency) as avg_latency,
                    AVG(jitter) as avg_jitter
                FROM performance_metrics
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('性能指标计算失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取带宽趋势
     * @return array
     */
    private function getBandwidthTrend(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    SUM(bytes) as total_bytes
                FROM traffic_data
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY hour
                ORDER BY hour ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取带宽趋势失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 检查带宽突发
     * @param array $features
     * @return bool
     */
    private function isBandwidthBurst(array $features): bool {
        $threshold = $this->config['bandwidth_burst_threshold'] ?? 2.0;
        return isset($features['bandwidth']) && $features['bandwidth'] > $threshold;
    }
    
    /**
     * 检查协议异常
     * @param array $features
     * @return bool
     */
    private function isProtocolAnomaly(array $features): bool {
        $normalProtocols = ['TCP', 'UDP', 'ICMP'];
        return isset($features['protocol']) && !in_array($features['protocol'], $normalProtocols);
    }
    
    /**
     * 检查连接数异常
     * @param array $features
     * @return bool
     */
    private function isConnectionAnomaly(array $features): bool {
        $threshold = $this->config['connection_threshold'] ?? 1000;
        return isset($features['connections']) && $features['connections'] > $threshold;
    }
    
    /**
     * 保存分析结果
     * @param array $analysis
     * @return bool
     */
    private function saveAnalysis(array $analysis): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO traffic_analysis (
                    bandwidth_usage,
                    protocol_distribution,
                    top_ips,
                    anomalies,
                    performance_metrics,
                    created_at
                ) VALUES (
                    :bandwidth_usage,
                    :protocol_distribution,
                    :top_ips,
                    :anomalies,
                    :performance_metrics,
                    NOW()
                )
            ");
            
            return $stmt->execute([
                'bandwidth_usage' => json_encode($analysis['bandwidth_usage']),
                'protocol_distribution' => json_encode($analysis['protocol_distribution']),
                'top_ips' => json_encode($analysis['top_ips']),
                'anomalies' => json_encode($analysis['anomalies']),
                'performance_metrics' => json_encode($analysis['performance_metrics'])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('保存分析结果失败：' . $e->getMessage());
            return false;
        }
    }
} 