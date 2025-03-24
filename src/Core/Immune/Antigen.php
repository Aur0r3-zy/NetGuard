<?php

namespace Core\Immune;

class Antigen {
    private $features;
    private $threshold;
    
    public function __construct() {
        $this->features = [];
        $this->threshold = 0.8;
    }
    
    public function match($features) {
        $this->features = $features;
        
        // 计算特征匹配度
        $matchScore = $this->calculateMatchScore();
        
        // 判断是否超过阈值
        if ($matchScore >= $this->threshold) {
            return [
                'matched' => true,
                'score' => $matchScore,
                'features' => $this->features
            ];
        }
        
        return [
            'matched' => false,
            'score' => $matchScore,
            'features' => $this->features
        ];
    }
    
    private function calculateMatchScore() {
        // 实现特征匹配度计算逻辑
        return 0.85;
    }
    
    public function setThreshold($threshold) {
        $this->threshold = $threshold;
    }
} 