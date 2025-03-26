<?php

namespace App\Core\Network;

use App\Core\Logger\Logger;
use App\Data\FeatureExtractor;
use App\Data\Normalizer;

class PacketProcessor {
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
     * 处理网络数据包
     * @param array $packet 数据包数据
     * @return array 处理结果
     */
    public function processPacket(array $packet): array {
        try {
            // 1. 数据包验证
            if (!$this->validatePacket($packet)) {
                throw new \Exception('无效的数据包格式');
            }
            
            // 2. 特征提取
            $features = $this->featureExtractor->extract($packet);
            
            // 3. 特征标准化
            $normalizedFeatures = $this->normalizer->normalize($features);
            
            // 4. 异常检测
            $anomalyScore = $this->detectAnomaly($normalizedFeatures);
            
            // 5. 威胁检测
            $threats = $this->detectThreats($packet, $normalizedFeatures);
            
            // 6. 保存处理结果
            $result = $this->saveResults($packet, $features, $anomalyScore, $threats);
            
            return [
                'status' => 'success',
                'data' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('数据包处理失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证数据包格式
     * @param array $packet
     * @return bool
     */
    private function validatePacket(array $packet): bool {
        $requiredFields = [
            'timestamp',
            'source_ip',
            'destination_ip',
            'protocol',
            'length',
            'payload'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($packet[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 检测异常
     * @param array $features
     * @return float
     */
    private function detectAnomaly(array $features): float {
        try {
            // 使用统计方法检测异常
            $mean = array_sum($features) / count($features);
            $stdDev = $this->calculateStdDev($features, $mean);
            
            // 计算异常分数
            $anomalyScores = array_map(function($value) use ($mean, $stdDev) {
                return abs($value - $mean) / $stdDev;
            }, $features);
            
            return max($anomalyScores);
        } catch (\Exception $e) {
            $this->logger->error('异常检测失败：' . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * 检测威胁
     * @param array $packet
     * @param array $features
     * @return array
     */
    private function detectThreats(array $packet, array $features): array {
        $threats = [];
        
        try {
            // 1. 检查可疑IP
            if ($this->isSuspiciousIP($packet['source_ip'])) {
                $threats[] = [
                    'type' => 'suspicious_ip',
                    'severity' => 'high',
                    'description' => '可疑源IP地址'
                ];
            }
            
            // 2. 检查协议异常
            if ($this->isProtocolAnomaly($packet['protocol'], $features)) {
                $threats[] = [
                    'type' => 'protocol_anomaly',
                    'severity' => 'medium',
                    'description' => '协议使用异常'
                ];
            }
            
            // 3. 检查数据包大小异常
            if ($this->isPacketSizeAnomaly($packet['length'], $features)) {
                $threats[] = [
                    'type' => 'size_anomaly',
                    'severity' => 'medium',
                    'description' => '数据包大小异常'
                ];
            }
            
            // 4. 检查负载内容
            if ($this->isMaliciousPayload($packet['payload'])) {
                $threats[] = [
                    'type' => 'malicious_payload',
                    'severity' => 'critical',
                    'description' => '恶意负载内容'
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('威胁检测失败：' . $e->getMessage());
        }
        
        return $threats;
    }
    
    /**
     * 保存处理结果
     * @param array $packet
     * @param array $features
     * @param float $anomalyScore
     * @param array $threats
     * @return array
     */
    private function saveResults(array $packet, array $features, float $anomalyScore, array $threats): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO packet_analysis (
                    timestamp,
                    source_ip,
                    destination_ip,
                    protocol,
                    packet_length,
                    features,
                    anomaly_score,
                    threats,
                    created_at
                ) VALUES (
                    :timestamp,
                    :source_ip,
                    :destination_ip,
                    :protocol,
                    :packet_length,
                    :features,
                    :anomaly_score,
                    :threats,
                    NOW()
                )
            ");
            
            $stmt->execute([
                'timestamp' => $packet['timestamp'],
                'source_ip' => $packet['source_ip'],
                'destination_ip' => $packet['destination_ip'],
                'protocol' => $packet['protocol'],
                'packet_length' => $packet['length'],
                'features' => json_encode($features),
                'anomaly_score' => $anomalyScore,
                'threats' => json_encode($threats)
            ]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'timestamp' => $packet['timestamp'],
                'anomaly_score' => $anomalyScore,
                'threats' => $threats
            ];
        } catch (\Exception $e) {
            $this->logger->error('保存处理结果失败：' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 检查IP是否可疑
     * @param string $ip
     * @return bool
     */
    private function isSuspiciousIP(string $ip): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM blacklist_ips
                WHERE ip = :ip
            ");
            $stmt->execute(['ip' => $ip]);
            return $stmt->fetch()['count'] > 0;
        } catch (\Exception $e) {
            $this->logger->error('IP检查失败：' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查协议使用是否异常
     * @param string $protocol
     * @param array $features
     * @return bool
     */
    private function isProtocolAnomaly(string $protocol, array $features): bool {
        $normalProtocols = ['TCP', 'UDP', 'ICMP'];
        return !in_array($protocol, $normalProtocols);
    }
    
    /**
     * 检查数据包大小是否异常
     * @param int $length
     * @param array $features
     * @return bool
     */
    private function isPacketSizeAnomaly(int $length, array $features): bool {
        $maxSize = $this->config['max_packet_size'] ?? 65535;
        return $length > $maxSize;
    }
    
    /**
     * 检查负载是否包含恶意内容
     * @param string $payload
     * @return bool
     */
    private function isMaliciousPayload(string $payload): bool {
        $patterns = [
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/shell_exec\s*\(/i',
            '/system\s*\(/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $payload)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 计算标准差
     * @param array $values
     * @param float $mean
     * @return float
     */
    private function calculateStdDev(array $values, float $mean): float {
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
} 