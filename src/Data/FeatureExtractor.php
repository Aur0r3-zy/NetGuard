<?php

namespace App\Data;

/**
 * 特征提取器类
 * 用于从原始数据中提取有意义的特征
 */
class FeatureExtractor {
    private array $features = [];
    private array $config;
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'enable_cache' => true,
            'cache_ttl' => 3600,
            'normalize_features' => true,
            'feature_weights' => []
        ], $config);
    }
    
    /**
     * 提取特征
     * @param array $data 原始数据
     * @return array 提取的特征
     */
    public function extract(array $data): array {
        if (empty($data)) {
            throw new \InvalidArgumentException('输入数据不能为空');
        }

        // 使用缓存
        $cacheKey = md5(json_encode($data));
        if ($this->config['enable_cache'] && $this->getFromCache($cacheKey)) {
            return $this->features;
        }

        $features = [];
        
        // 提取统计特征
        $features['statistical'] = $this->extractStatisticalFeatures($data);
        
        // 提取时间特征
        $features['temporal'] = $this->extractTemporalFeatures($data);
        
        // 提取协议特征
        $features['protocol'] = $this->extractProtocolFeatures($data);
        
        // 提取流量特征
        $features['traffic'] = $this->extractTrafficFeatures($data);
        
        // 提取安全特征
        $features['security'] = $this->extractSecurityFeatures($data);
        
        // 提取网络特征
        $features['network'] = $this->extractNetworkFeatures($data);
        
        // 特征归一化
        if ($this->config['normalize_features']) {
            $features = $this->normalizeFeatures($features);
        }
        
        // 应用特征权重
        if (!empty($this->config['feature_weights'])) {
            $features = $this->applyFeatureWeights($features);
        }
        
        $this->features = $features;
        
        // 保存到缓存
        if ($this->config['enable_cache']) {
            $this->saveToCache($cacheKey);
        }
        
        return $features;
    }
    
    /**
     * 提取统计特征
     * @param array $data 原始数据
     * @return array 统计特征
     */
    private function extractStatisticalFeatures(array $data): array {
        $values = array_column($data, 'value');
        $count = count($values);
        
        if ($count === 0) {
            return [
                'mean' => 0,
                'std_dev' => 0,
                'skewness' => 0,
                'kurtosis' => 0,
                'median' => 0,
                'mode' => 0,
                'range' => 0,
                'iqr' => 0
            ];
        }
        
        sort($values);
        $mean = array_sum($values) / $count;
        $variance = $this->calculateVariance($values, $mean);
        $stdDev = sqrt($variance);
        
        return [
            'mean' => $mean,
            'std_dev' => $stdDev,
            'skewness' => $this->calculateSkewness($values, $mean, $stdDev),
            'kurtosis' => $this->calculateKurtosis($values, $mean, $stdDev),
            'median' => $this->calculateMedian($values),
            'mode' => $this->calculateMode($values),
            'range' => max($values) - min($values),
            'iqr' => $this->calculateIQR($values)
        ];
    }
    
    /**
     * 提取时间特征
     * @param array $data 原始数据
     * @return array 时间特征
     */
    private function extractTemporalFeatures(array $data): array {
        if (empty($data)) {
            return [
                'duration' => 0,
                'packets_per_second' => 0,
                'inter_arrival_time' => [],
                'burst_count' => 0,
                'burst_duration' => 0
            ];
        }
        
        $timestamps = array_column($data, 'timestamp');
        sort($timestamps);
        
        $duration = max($timestamps) - min($timestamps);
        $packetsPerSecond = $duration > 0 ? count($data) / $duration : 0;
        
        // 计算到达间隔时间
        $interArrivalTimes = [];
        for ($i = 1; $i < count($timestamps); $i++) {
            $interArrivalTimes[] = $timestamps[$i] - $timestamps[$i-1];
        }
        
        // 检测突发流量
        $burstInfo = $this->detectBursts($interArrivalTimes);
        
        return [
            'duration' => $duration,
            'packets_per_second' => $packetsPerSecond,
            'inter_arrival_time' => [
                'mean' => array_sum($interArrivalTimes) / count($interArrivalTimes),
                'std_dev' => $this->calculateStdDev($interArrivalTimes),
                'max' => max($interArrivalTimes),
                'min' => min($interArrivalTimes)
            ],
            'burst_count' => $burstInfo['count'],
            'burst_duration' => $burstInfo['duration']
        ];
    }
    
    /**
     * 提取协议特征
     * @param array $data 原始数据
     * @return array 协议特征
     */
    private function extractProtocolFeatures(array $data): array {
        $protocols = [];
        $ports = [];
        
        foreach ($data as $item) {
            if (isset($item['protocol'])) {
                $protocols[$item['protocol']] = ($protocols[$item['protocol']] ?? 0) + 1;
            }
            if (isset($item['port'])) {
                $ports[$item['port']] = ($ports[$item['port']] ?? 0) + 1;
            }
        }
        
        return [
            'protocol_distribution' => $protocols,
            'unique_protocols' => count($protocols),
            'port_distribution' => $ports,
            'unique_ports' => count($ports),
            'protocol_entropy' => $this->calculateEntropy($protocols),
            'port_entropy' => $this->calculateEntropy($ports)
        ];
    }
    
    /**
     * 提取流量特征
     * @param array $data 原始数据
     * @return array 流量特征
     */
    private function extractTrafficFeatures(array $data): array {
        $bytes = array_column($data, 'data_length');
        $totalBytes = array_sum($bytes);
        
        return [
            'total_bytes' => $totalBytes,
            'average_packet_size' => count($bytes) > 0 ? $totalBytes / count($bytes) : 0,
            'packet_size_distribution' => [
                'mean' => array_sum($bytes) / count($bytes),
                'std_dev' => $this->calculateStdDev($bytes),
                'max' => max($bytes),
                'min' => min($bytes)
            ],
            'byte_entropy' => $this->calculateEntropy($bytes),
            'packet_size_entropy' => $this->calculateEntropy(array_count_values($bytes))
        ];
    }
    
    /**
     * 提取安全特征
     * @param array $data 原始数据
     * @return array 安全特征
     */
    private function extractSecurityFeatures(array $data): array {
        $suspiciousPatterns = 0;
        $maliciousIPs = [];
        $blacklistedPorts = [];
        
        foreach ($data as $item) {
            // 检查可疑模式
            if ($this->isSuspiciousPattern($item)) {
                $suspiciousPatterns++;
            }
            
            // 检查恶意IP
            if (isset($item['source_ip']) && $this->isMaliciousIP($item['source_ip'])) {
                $maliciousIPs[$item['source_ip']] = true;
            }
            
            // 检查黑名单端口
            if (isset($item['port']) && $this->isBlacklistedPort($item['port'])) {
                $blacklistedPorts[$item['port']] = true;
            }
        }
        
        return [
            'suspicious_patterns' => $suspiciousPatterns,
            'malicious_ips' => count($maliciousIPs),
            'blacklisted_ports' => count($blacklistedPorts),
            'security_score' => $this->calculateSecurityScore($data)
        ];
    }
    
    /**
     * 提取网络特征
     * @param array $data 原始数据
     * @return array 网络特征
     */
    private function extractNetworkFeatures(array $data): array {
        $sourceIPs = [];
        $destIPs = [];
        $sourcePorts = [];
        $destPorts = [];
        
        foreach ($data as $item) {
            if (isset($item['source_ip'])) {
                $sourceIPs[$item['source_ip']] = true;
            }
            if (isset($item['dest_ip'])) {
                $destIPs[$item['dest_ip']] = true;
            }
            if (isset($item['source_port'])) {
                $sourcePorts[$item['source_port']] = true;
            }
            if (isset($item['dest_port'])) {
                $destPorts[$item['dest_port']] = true;
            }
        }
        
        return [
            'unique_source_ips' => count($sourceIPs),
            'unique_dest_ips' => count($destIPs),
            'unique_source_ports' => count($sourcePorts),
            'unique_dest_ports' => count($destPorts),
            'ip_entropy' => $this->calculateEntropy(array_merge($sourceIPs, $destIPs)),
            'port_entropy' => $this->calculateEntropy(array_merge($sourcePorts, $destPorts))
        ];
    }
    
    /**
     * 计算方差
     * @param array $values 数值数组
     * @param float $mean 平均值
     * @return float 方差
     */
    private function calculateVariance(array $values, float $mean): float {
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        return array_sum($squaredDiffs) / count($values);
    }
    
    /**
     * 计算偏度
     * @param array $values 数值数组
     * @param float $mean 平均值
     * @param float $stdDev 标准差
     * @return float 偏度
     */
    private function calculateSkewness(array $values, float $mean, float $stdDev): float {
        if ($stdDev === 0) return 0;
        
        $cubedDiffs = array_map(function($value) use ($mean, $stdDev) {
            return pow(($value - $mean) / $stdDev, 3);
        }, $values);
        
        return array_sum($cubedDiffs) / count($values);
    }
    
    /**
     * 计算峰度
     * @param array $values 数值数组
     * @param float $mean 平均值
     * @param float $stdDev 标准差
     * @return float 峰度
     */
    private function calculateKurtosis(array $values, float $mean, float $stdDev): float {
        if ($stdDev === 0) return 0;
        
        $fourthPowerDiffs = array_map(function($value) use ($mean, $stdDev) {
            return pow(($value - $mean) / $stdDev, 4);
        }, $values);
        
        return array_sum($fourthPowerDiffs) / count($values);
    }
    
    /**
     * 计算中位数
     * @param array $values 数值数组
     * @return float 中位数
     */
    private function calculateMedian(array $values): float {
        $count = count($values);
        if ($count === 0) return 0;
        
        $middle = floor($count / 2);
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        return $values[$middle];
    }
    
    /**
     * 计算众数
     * @param array $values 数值数组
     * @return float 众数
     */
    private function calculateMode(array $values): float {
        $counts = array_count_values($values);
        arsort($counts);
        return key($counts);
    }
    
    /**
     * 计算四分位距
     * @param array $values 数值数组
     * @return float 四分位距
     */
    private function calculateIQR(array $values): float {
        $count = count($values);
        if ($count < 4) return 0;
        
        $q1Index = floor($count * 0.25);
        $q3Index = floor($count * 0.75);
        
        return $values[$q3Index] - $values[$q1Index];
    }
    
    /**
     * 检测突发流量
     * @param array $interArrivalTimes 到达间隔时间数组
     * @return array 突发流量信息
     */
    private function detectBursts(array $interArrivalTimes): array {
        if (empty($interArrivalTimes)) {
            return ['count' => 0, 'duration' => 0];
        }
        
        $mean = array_sum($interArrivalTimes) / count($interArrivalTimes);
        $stdDev = $this->calculateStdDev($interArrivalTimes);
        $threshold = $mean + 2 * $stdDev;
        
        $burstCount = 0;
        $burstDuration = 0;
        $inBurst = false;
        
        foreach ($interArrivalTimes as $time) {
            if ($time < $threshold) {
                if (!$inBurst) {
                    $burstCount++;
                    $inBurst = true;
                }
                $burstDuration += $time;
            } else {
                $inBurst = false;
            }
        }
        
        return [
            'count' => $burstCount,
            'duration' => $burstDuration
        ];
    }
    
    /**
     * 计算熵
     * @param array $values
     * @return float
     */
    private function calculateEntropy(array $values): float {
        $total = array_sum($values);
        if ($total === 0) {
            return 0.0;
        }
        
        $entropy = 0.0;
        foreach ($values as $value) {
            $probability = $value / $total;
            if ($probability > 0) {
                $entropy -= $probability * $this->log2($probability);
            }
        }
        
        return $entropy;
    }
    
    /**
     * 检查可疑模式
     * @param array $item 数据项
     * @return bool 是否可疑
     */
    private function isSuspiciousPattern(array $item): bool {
        // 实现可疑模式检测逻辑
        return false;
    }
    
    /**
     * 检查恶意IP
     * @param string $ip IP地址
     * @return bool 是否恶意
     */
    private function isMaliciousIP(string $ip): bool {
        // 实现恶意IP检测逻辑
        return false;
    }
    
    /**
     * 检查黑名单端口
     * @param int $port 端口号
     * @return bool 是否在黑名单中
     */
    private function isBlacklistedPort(int $port): bool {
        // 实现端口黑名单检查逻辑
        return false;
    }
    
    /**
     * 计算安全分数
     * @param array $data 原始数据
     * @return float 安全分数
     */
    private function calculateSecurityScore(array $data): float {
        // 实现安全分数计算逻辑
        return 0.0;
    }
    
    /**
     * 特征归一化
     * @param array $features 特征数组
     * @return array 归一化后的特征
     */
    private function normalizeFeatures(array $features): array {
        // 实现特征归一化逻辑
        return $features;
    }
    
    /**
     * 应用特征权重
     * @param array $features 特征数组
     * @return array 加权后的特征
     */
    private function applyFeatureWeights(array $features): array {
        // 实现特征权重应用逻辑
        return $features;
    }
    
    /**
     * 从缓存获取数据
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    private function getFromCache(string $key): bool {
        // 实现缓存获取逻辑
        return false;
    }
    
    /**
     * 保存数据到缓存
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    private function saveToCache(string $key): bool {
        // 实现缓存保存逻辑
        return false;
    }
    
    /**
     * 获取提取的特征
     * @return array 特征数组
     */
    public function getFeatures(): array {
        return $this->features;
    }
    
    /**
     * 计算标准差
     * @param array $values
     * @return float
     */
    private function calculateStdDev(array $values): float {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
    
    /**
     * 计算以2为底的对数
     * @param float $value
     * @return float
     */
    private function log2(float $value): float {
        return log($value, 2);
    }
} 