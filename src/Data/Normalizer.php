<?php

namespace Data;

class Normalizer {
    private $minMax;
    private $meanStd;
    
    public function __construct() {
        $this->minMax = [];
        $this->meanStd = [];
    }
    
    public function normalize($data) {
        // 计算统计信息
        $this->calculateStatistics($data);
        
        // 标准化数据
        $normalized = [];
        foreach ($data as $item) {
            $normalized[] = $this->normalizeItem($item);
        }
        
        return $normalized;
    }
    
    private function calculateStatistics($data) {
        $values = array_column($data, 'value');
        
        // 计算最小值和最大值
        $this->minMax = [
            'min' => min($values),
            'max' => max($values)
        ];
        
        // 计算均值和标准差
        $this->meanStd = [
            'mean' => array_sum($values) / count($values),
            'std' => $this->calculateStdDev($values)
        ];
    }
    
    private function calculateStdDev($values) {
        $mean = $this->meanStd['mean'];
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
    
    private function normalizeItem($item) {
        // 最小-最大标准化
        $minMaxNormalized = ($item['value'] - $this->minMax['min']) / 
            ($this->minMax['max'] - $this->minMax['min']);
        
        // Z-score标准化
        $zScoreNormalized = ($item['value'] - $this->meanStd['mean']) / 
            $this->meanStd['std'];
        
        return [
            'original' => $item['value'],
            'min_max_normalized' => $minMaxNormalized,
            'z_score_normalized' => $zScoreNormalized
        ];
    }
    
    public function getStatistics() {
        return [
            'min_max' => $this->minMax,
            'mean_std' => $this->meanStd
        ];
    }
} 