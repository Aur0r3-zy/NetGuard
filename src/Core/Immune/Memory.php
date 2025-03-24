<?php

namespace Core\Immune;

class Memory {
    private $antibodies;
    private $maxSize;
    
    public function __construct() {
        $this->antibodies = [];
        $this->maxSize = 1000;
    }
    
    public function setMaxSize($size) {
        $this->maxSize = $size;
        $this->trim($size);
    }
    
    public function update($antibodies) {
        foreach ($antibodies as $antibody) {
            if ($antibody['affinity'] >= 0.8) { // 高亲和度抗体存入记忆库
                $this->add($antibody);
            }
        }
    }
    
    public function add($antibody) {
        // 检查是否已存在相似抗体
        $similarIndex = $this->findSimilar($antibody);
        
        if ($similarIndex !== false) {
            // 更新已存在的抗体
            $this->antibodies[$similarIndex]['concentration'] += 1;
        } else {
            // 添加新抗体
            $this->antibodies[] = $antibody;
        }
        
        // 确保不超过最大容量
        if (count($this->antibodies) > $this->maxSize) {
            $this->trim($this->maxSize);
        }
    }
    
    public function findSimilar($antibody) {
        foreach ($this->antibodies as $index => $memoryAntibody) {
            if ($this->calculateSimilarity($antibody['features'], $memoryAntibody['features']) >= 0.9) {
                return $index;
            }
        }
        return false;
    }
    
    public function calculateMemoryAffinity($antibody) {
        $maxAffinity = 0;
        
        foreach ($this->antibodies as $memoryAntibody) {
            $similarity = $this->calculateSimilarity($antibody['features'], $memoryAntibody['features']);
            $affinity = $similarity * $memoryAntibody['concentration'];
            $maxAffinity = max($maxAffinity, $affinity);
        }
        
        return $maxAffinity;
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
    
    public function cleanup($threshold) {
        $this->antibodies = array_filter($this->antibodies, function($antibody) use ($threshold) {
            return $antibody['affinity'] >= $threshold;
        });
    }
    
    public function mergeSimilar() {
        $merged = [];
        $used = [];
        
        foreach ($this->antibodies as $i => $antibody1) {
            if (isset($used[$i])) {
                continue;
            }
            
            $group = [$antibody1];
            $used[$i] = true;
            
            foreach ($this->antibodies as $j => $antibody2) {
                if ($i !== $j && !isset($used[$j])) {
                    if ($this->calculateSimilarity($antibody1['features'], $antibody2['features']) >= 0.9) {
                        $group[] = $antibody2;
                        $used[$j] = true;
                    }
                }
            }
            
            // 合并相似抗体
            if (count($group) > 1) {
                $merged[] = $this->mergeAntibodies($group);
            } else {
                $merged[] = $group[0];
            }
        }
        
        $this->antibodies = $merged;
    }
    
    private function mergeAntibodies($group) {
        $merged = [
            'features' => [],
            'affinity' => 0,
            'concentration' => 0
        ];
        
        $featureCount = count($group[0]['features']);
        
        // 合并特征
        for ($i = 0; $i < $featureCount; $i++) {
            $sum = 0;
            foreach ($group as $antibody) {
                $sum += $antibody['features'][$i];
            }
            $merged['features'][$i] = $sum / count($group) >= 0.5 ? 1 : 0;
        }
        
        // 计算平均亲和度和总浓度
        foreach ($group as $antibody) {
            $merged['affinity'] += $antibody['affinity'];
            $merged['concentration'] += $antibody['concentration'];
        }
        
        $merged['affinity'] /= count($group);
        
        return $merged;
    }
    
    public function getSize() {
        return count($this->antibodies);
    }
    
    public function trim($size) {
        if (count($this->antibodies) > $size) {
            // 按亲和度排序
            usort($this->antibodies, function($a, $b) {
                return $b['affinity'] <=> $a['affinity'];
            });
            
            // 保留前size个抗体
            $this->antibodies = array_slice($this->antibodies, 0, $size);
        }
    }
    
    public function getAntibodies() {
        return $this->antibodies;
    }
    
    public function clear() {
        $this->antibodies = [];
    }
} 