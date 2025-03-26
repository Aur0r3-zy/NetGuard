<?php

namespace Core\Immune;

class Antigen {
    private $features;
    private $type;
    private $timestamp;
    private $source;
    private $severity;
    private $metadata;
    
    public function __construct() {
        $this->features = [];
        $this->type = '';
        $this->timestamp = time();
        $this->source = '';
        $this->severity = 0;
        $this->metadata = [];
    }
    
    public function create($data) {
        $this->features = $this->extractFeatures($data);
        $this->type = $data['type'] ?? 'unknown';
        $this->timestamp = time();
        $this->source = $data['source'] ?? 'unknown';
        $this->severity = $data['severity'] ?? 0;
        $this->metadata = $data['metadata'] ?? [];
        
        return [
            'features' => $this->features,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
            'source' => $this->source,
            'severity' => $this->severity,
            'metadata' => $this->metadata
        ];
    }
    
    private function extractFeatures($data) {
        $features = [];
        
        // 提取数值特征
        if (isset($data['features'])) {
            foreach ($data['features'] as $feature) {
                $features[] = $this->normalizeFeature($feature);
            }
        }
        
        // 提取时间特征
        if (isset($data['timestamp'])) {
            $timeFeatures = $this->extractTimeFeatures($data['timestamp']);
            $features = array_merge($features, $timeFeatures);
        }
        
        // 提取类别特征
        if (isset($data['type'])) {
            $typeFeatures = $this->extractTypeFeatures($data['type']);
            $features = array_merge($features, $typeFeatures);
        }
        
        return $features;
    }
    
    private function normalizeFeature($value) {
        // 使用Min-Max标准化
        $min = 0;
        $max = 1;
        return ($value - $min) / ($max - $min);
    }
    
    private function extractTimeFeatures($timestamp) {
        $features = [];
        $date = new \DateTime("@$timestamp");
        
        // 提取时间特征
        $features[] = $date->format('H') / 24; // 小时
        $features[] = $date->format('i') / 60; // 分钟
        $features[] = $date->format('s') / 60; // 秒
        $features[] = $date->format('N') / 7;  // 星期几
        $features[] = $date->format('j') / 31; // 日期
        
        return $features;
    }
    
    private function extractTypeFeatures($type) {
        $features = [];
        $typeMap = [
            'sql_injection' => [1, 0, 0, 0],
            'xss' => [0, 1, 0, 0],
            'csrf' => [0, 0, 1, 0],
            'file_inclusion' => [0, 0, 0, 1],
            'unknown' => [0, 0, 0, 0]
        ];
        
        $features = $typeMap[$type] ?? $typeMap['unknown'];
        return $features;
    }
    
    public function getFeatures() {
        return $this->features;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    public function getSource() {
        return $this->source;
    }
    
    public function getSeverity() {
        return $this->severity;
    }
    
    public function getMetadata() {
        return $this->metadata;
    }
    
    public function calculateSimilarity($otherFeatures) {
        if (empty($this->features) || empty($otherFeatures)) {
            return 0;
        }
        
        $sum = 0;
        $count = min(count($this->features), count($otherFeatures));
        
        for ($i = 0; $i < $count; $i++) {
            $sum += abs($this->features[$i] - $otherFeatures[$i]);
        }
        
        return 1 - ($sum / $count);
    }
    
    public function isAnomaly($threshold = 0.8) {
        // 基于特征值判断是否为异常
        $anomalyScore = $this->calculateAnomalyScore();
        return $anomalyScore > $threshold;
    }
    
    private function calculateAnomalyScore() {
        if (empty($this->features)) {
            return 0;
        }
        
        // 计算特征值的统计特性
        $mean = array_sum($this->features) / count($this->features);
        $variance = 0;
        
        foreach ($this->features as $feature) {
            $variance += pow($feature - $mean, 2);
        }
        $variance /= count($this->features);
        
        // 计算异常分数
        $score = 0;
        foreach ($this->features as $feature) {
            $zScore = abs($feature - $mean) / sqrt($variance);
            $score += $zScore;
        }
        
        return $score / count($this->features);
    }
} 