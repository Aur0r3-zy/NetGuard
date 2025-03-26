<?php

namespace Data;

class Preprocessor {
    private $normalizer;
    private $featureExtractor;
    
    public function __construct() {
        $this->normalizer = new Normalizer();
        $this->featureExtractor = new FeatureExtractor();
    }
    
    public function preprocess($data) {
        // 数据清洗
        $cleanedData = $this->cleanData($data);
        
        // 数据标准化
        $normalizedData = $this->normalizer->normalize($cleanedData);
        
        // 特征提取
        $features = $this->featureExtractor->extract($normalizedData);
        
        return [
            'original_data' => $data,
            'cleaned_data' => $cleanedData,
            'normalized_data' => $normalizedData,
            'features' => $features
        ];
    }
    
    private function cleanData($data) {
        $cleaned = [];
        
        foreach ($data as $item) {
            // 移除空值
            if (empty($item)) {
                continue;
            }
            
            // 移除异常值
            if ($this->isOutlier($item)) {
                continue;
            }
            
            $cleaned[] = $item;
        }
        
        return $cleaned;
    }
    
    private function isOutlier($value) {
        // 实现异常值检测逻辑
        return false;
    }
    
    public function getNormalizer() {
        return $this->normalizer;
    }
    
    public function getFeatureExtractor() {
        return $this->featureExtractor;
    }
} 