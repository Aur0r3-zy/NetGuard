<?php

namespace Core\Immune;

class Antigen {
    private $features;
    private $patterns;
    
    public function __construct() {
        $this->features = [];
        $this->patterns = [];
    }
    
    public function match($features) {
        $matches = [];
        
        foreach ($this->patterns as $pattern) {
            $similarity = $this->calculateSimilarity($features, $pattern['features']);
            if ($similarity >= $pattern['threshold']) {
                $matches[] = [
                    'pattern' => $pattern,
                    'similarity' => $similarity
                ];
            }
        }
        
        return $matches;
    }
    
    public function calculateAffinity($features) {
        $maxSimilarity = 0;
        
        foreach ($this->patterns as $pattern) {
            $similarity = $this->calculateSimilarity($features, $pattern['features']);
            $maxSimilarity = max($maxSimilarity, $similarity);
        }
        
        return $maxSimilarity;
    }
    
    private function calculateSimilarity($features1, $features2) {
        if (count($features1) !== count($features2)) {
            return 0;
        }
        
        $sum = 0;
        $count = count($features1);
        
        for ($i = 0; $i < $count; $i++) {
            if ($features1[$i] === $features2[$i]) {
                $sum++;
            }
        }
        
        return $sum / $count;
    }
    
    public function addPattern($features, $threshold = 0.7) {
        $this->patterns[] = [
            'features' => $features,
            'threshold' => $threshold
        ];
    }
    
    public function removePattern($index) {
        if (isset($this->patterns[$index])) {
            unset($this->patterns[$index]);
            $this->patterns = array_values($this->patterns);
        }
    }
    
    public function getPatterns() {
        return $this->patterns;
    }
    
    public function clearPatterns() {
        $this->patterns = [];
    }
} 