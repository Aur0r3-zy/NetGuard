<?php

namespace Core\Immune;

class Antibody {
    private $features;
    private $affinity;
    private $concentration;
    private $age;
    private $lastUpdate;
    private $mutationCount;
    
    public function __construct() {
        $this->features = [];
        $this->affinity = 0;
        $this->concentration = 1.0;
        $this->age = 0;
        $this->lastUpdate = time();
        $this->mutationCount = 0;
    }
    
    public function generate($matchResult) {
        $antibodies = [];
        
        foreach ($matchResult as $match) {
            $antibody = [
                'features' => $this->generateFeatures($match['pattern']['features']),
                'affinity' => $match['similarity'],
                'concentration' => 1.0,
                'age' => 0,
                'lastUpdate' => time(),
                'mutationCount' => 0
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
            'concentration' => 1.0,
            'age' => 0,
            'lastUpdate' => time(),
            'mutationCount' => 0
        ];
    }
    
    private function generateFeatures($patternFeatures) {
        $features = [];
        
        foreach ($patternFeatures as $feature) {
            // 添加自适应变异率
            $mutationRate = $this->calculateAdaptiveMutationRate();
            if (mt_rand() / mt_getrandmax() < $mutationRate) {
                $features[] = $feature === 1 ? 0 : 1;
                $this->mutationCount++;
            } else {
                $features[] = $feature;
            }
        }
        
        return $features;
    }
    
    private function calculateAdaptiveMutationRate() {
        // 基于年龄和变异次数计算自适应变异率
        $baseRate = 0.1;
        $ageFactor = min(1.0, $this->age / 1000);
        $mutationFactor = min(1.0, $this->mutationCount / 100);
        
        return $baseRate * (1 + $ageFactor) * (1 - $mutationFactor);
    }
    
    public function updateAffinity($newAffinity) {
        $this->affinity = $newAffinity;
        $this->lastUpdate = time();
    }
    
    public function updateConcentration($decayRate) {
        $this->concentration *= (1 - $decayRate);
        $this->age++;
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
    
    public function getAge() {
        return $this->age;
    }
    
    public function getLastUpdate() {
        return $this->lastUpdate;
    }
    
    public function getMutationCount() {
        return $this->mutationCount;
    }
    
    public function clone() {
        return [
            'features' => $this->features,
            'affinity' => $this->affinity,
            'concentration' => $this->concentration,
            'age' => $this->age,
            'lastUpdate' => $this->lastUpdate,
            'mutationCount' => $this->mutationCount
        ];
    }
    
    public function isMature() {
        return $this->age >= 1000 && $this->affinity >= 0.8;
    }
    
    public function isExhausted() {
        return $this->age >= 5000 || $this->concentration <= 0.1;
    }
} 