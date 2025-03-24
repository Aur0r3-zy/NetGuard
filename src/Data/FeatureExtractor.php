<?php

namespace Data;

class FeatureExtractor {
    private $features;
    
    public function __construct() {
        $this->features = [];
    }
    
    public function extract($data) {
        $features = [];
        
        // 提取统计特征
        $features['statistical'] = $this->extractStatisticalFeatures($data);
        
        // 提取时间特征
        $features['temporal'] = $this->extractTemporalFeatures($data);
        
        // 提取协议特征
        $features['protocol'] = $this->extractProtocolFeatures($data);
        
        // 提取流量特征
        $features['traffic'] = $this->extractTrafficFeatures($data);
        
        $this->features = $features;
        return $features;
    }
    
    private function extractStatisticalFeatures($data) {
        return [
            'mean' => $this->calculateMean($data),
            'std_dev' => $this->calculateStdDev($data),
            'skewness' => $this->calculateSkewness($data),
            'kurtosis' => $this->calculateKurtosis($data)
        ];
    }
    
    private function extractTemporalFeatures($data) {
        return [
            'duration' => $this->calculateDuration($data),
            'packets_per_second' => $this->calculatePacketsPerSecond($data),
            'inter_arrival_time' => $this->calculateInterArrivalTime($data)
        ];
    }
    
    private function extractProtocolFeatures($data) {
        $protocols = [];
        foreach ($data as $item) {
            if (isset($item['protocol'])) {
                $protocols[$item['protocol']] = ($protocols[$item['protocol']] ?? 0) + 1;
            }
        }
        
        return [
            'protocol_distribution' => $protocols,
            'unique_protocols' => count($protocols)
        ];
    }
    
    private function extractTrafficFeatures($data) {
        return [
            'total_bytes' => $this->calculateTotalBytes($data),
            'average_packet_size' => $this->calculateAveragePacketSize($data),
            'packet_size_distribution' => $this->calculatePacketSizeDistribution($data)
        ];
    }
    
    private function calculateMean($data) {
        $values = array_column($data, 'value');
        return array_sum($values) / count($values);
    }
    
    private function calculateStdDev($data) {
        $mean = $this->calculateMean($data);
        $values = array_column($data, 'value');
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
    
    private function calculateSkewness($data) {
        // 实现偏度计算逻辑
        return 0;
    }
    
    private function calculateKurtosis($data) {
        // 实现峰度计算逻辑
        return 0;
    }
    
    private function calculateDuration($data) {
        if (empty($data)) {
            return 0;
        }
        $timestamps = array_column($data, 'timestamp');
        return max($timestamps) - min($timestamps);
    }
    
    private function calculatePacketsPerSecond($data) {
        $duration = $this->calculateDuration($data);
        return $duration > 0 ? count($data) / $duration : 0;
    }
    
    private function calculateInterArrivalTime($data) {
        // 实现到达间隔时间计算逻辑
        return [];
    }
    
    private function calculateTotalBytes($data) {
        return array_sum(array_column($data, 'data_length'));
    }
    
    private function calculateAveragePacketSize($data) {
        $totalBytes = $this->calculateTotalBytes($data);
        return count($data) > 0 ? $totalBytes / count($data) : 0;
    }
    
    private function calculatePacketSizeDistribution($data) {
        // 实现数据包大小分布计算逻辑
        return [];
    }
    
    public function getFeatures() {
        return $this->features;
    }
} 