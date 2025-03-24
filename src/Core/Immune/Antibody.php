<?php

namespace Core\Immune;

class Antibody {
    private $features;
    private $affinity;
    private $concentration;
    
    public function __construct() {
        $this->features = [];
        $this->affinity = 0;
        $this->concentration = 1.0;
    }
    
    public function generate($matchResult) {
        $antibodies = [];
        
        foreach ($matchResult as $match) {
            $antibody = [
                'features' => $this->generateFeatures($match['pattern']['features']),
                'affinity' => $match['similarity'],
                'concentration' => 1.0
            ];
            
            $antibodies[] = $antibody;
        }
        
        return $antibodies;
    }
    
    public function generateRandom() {
        $features = [];
        $featureCount = 10; // 默认特征数量
        
        for ($i = 0; $i < $featureCount; $i++) {
            $features[] = mt_rand(0, 1);
        }
        
        return [
            'features' => $features,
            'affinity' => 0,
            'concentration' => 1.0
        ];
    }
    
    private function generateFeatures($patternFeatures) {
        $features = [];
        
        foreach ($patternFeatures as $feature) {
            // 添加随机变异
            if (mt_rand() / mt_getrandmax() < 0.1) {
                $features[] = $feature === 1 ? 0 : 1;
            } else {
                $features[] = $feature;
            }
        }
        
        return $features;
    }
    
    public function updateAffinity($newAffinity) {
        $this->affinity = $newAffinity;
    }
    
    public function updateConcentration($decayRate) {
        $this->concentration *= (1 - $decayRate);
    }
    
    public function getFeatures() {
        return $this->features;
    }
    
    public function getAffinity() {
        return $this->affinity;
    }
    
    public function getConcentration() {
        return $this->concentration;
    }
    
    public function clone() {
        return [
            'features' => $this->features,
            'affinity' => $this->affinity,
            'concentration' => $this->concentration
        ];
    }
} 