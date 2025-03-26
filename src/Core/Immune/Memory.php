<?php

namespace Core\Immune;

class Memory {
    private $cells;
    private $maxSize;
    private $decayRate;
    private $updateInterval;
    private $lastUpdate;
    
    public function __construct($maxSize = 1000, $decayRate = 0.01, $updateInterval = 3600) {
        $this->cells = [];
        $this->maxSize = $maxSize;
        $this->decayRate = $decayRate;
        $this->updateInterval = $updateInterval;
        $this->lastUpdate = time();
    }
    
    public function add($antigen, $affinity) {
        // 检查是否需要更新记忆细胞
        $this->checkUpdate();
        
        // 创建新的记忆细胞
        $cell = [
            'features' => $antigen->getFeatures(),
            'type' => $antigen->getType(),
            'affinity' => $affinity,
            'timestamp' => time(),
            'source' => $antigen->getSource(),
            'severity' => $antigen->getSeverity(),
            'metadata' => $antigen->getMetadata(),
            'age' => 0,
            'hits' => 1
        ];
        
        // 检查是否已存在相似记忆细胞
        $existingIndex = $this->findSimilarCell($cell);
        
        if ($existingIndex !== false) {
            // 更新现有记忆细胞
            $this->updateCell($existingIndex, $cell);
        } else {
            // 添加新记忆细胞
            $this->cells[] = $cell;
            
            // 如果超出最大容量，移除最旧的记忆细胞
            if (count($this->cells) > $this->maxSize) {
                $this->removeOldestCell();
            }
        }
        
        return true;
    }
    
    private function checkUpdate() {
        $currentTime = time();
        if ($currentTime - $this->lastUpdate >= $this->updateInterval) {
            $this->updateCells();
            $this->lastUpdate = $currentTime;
        }
    }
    
    private function updateCells() {
        foreach ($this->cells as &$cell) {
            // 更新年龄
            $cell['age']++;
            
            // 应用衰减
            $cell['affinity'] *= (1 - $this->decayRate);
            
            // 如果亲和力太低，移除该记忆细胞
            if ($cell['affinity'] < 0.1) {
                $this->removeCell($cell);
            }
        }
        
        // 重新索引数组
        $this->cells = array_values($this->cells);
    }
    
    private function findSimilarCell($cell) {
        foreach ($this->cells as $index => $existingCell) {
            if ($this->calculateSimilarity($cell, $existingCell) > 0.8) {
                return $index;
            }
        }
        return false;
    }
    
    private function updateCell($index, $newCell) {
        $existingCell = &$this->cells[$index];
        
        // 更新命中次数
        $existingCell['hits']++;
        
        // 更新亲和力（使用加权平均）
        $weight = 1 / $existingCell['hits'];
        $existingCell['affinity'] = $weight * $newCell['affinity'] + 
                                  (1 - $weight) * $existingCell['affinity'];
        
        // 更新其他属性
        $existingCell['timestamp'] = time();
        $existingCell['source'] = $newCell['source'];
        $existingCell['severity'] = $newCell['severity'];
        $existingCell['metadata'] = $newCell['metadata'];
    }
    
    private function removeOldestCell() {
        $oldestIndex = 0;
        $oldestTimestamp = $this->cells[0]['timestamp'];
        
        foreach ($this->cells as $index => $cell) {
            if ($cell['timestamp'] < $oldestTimestamp) {
                $oldestIndex = $index;
                $oldestTimestamp = $cell['timestamp'];
            }
        }
        
        array_splice($this->cells, $oldestIndex, 1);
    }
    
    private function removeCell($cell) {
        $index = array_search($cell, $this->cells);
        if ($index !== false) {
            array_splice($this->cells, $index, 1);
        }
    }
    
    private function calculateSimilarity($cell1, $cell2) {
        if (empty($cell1['features']) || empty($cell2['features'])) {
            return 0;
        }
        
        $sum = 0;
        $count = min(count($cell1['features']), count($cell2['features']));
        
        for ($i = 0; $i < $count; $i++) {
            $sum += abs($cell1['features'][$i] - $cell2['features'][$i]);
        }
        
        return 1 - ($sum / $count);
    }
    
    public function match($antigen) {
        $matches = [];
        
        foreach ($this->cells as $cell) {
            $similarity = $this->calculateSimilarity(
                ['features' => $antigen->getFeatures()],
                $cell
            );
            
            if ($similarity > 0.7) {
                $matches[] = [
                    'cell' => $cell,
                    'similarity' => $similarity
                ];
            }
        }
        
        // 按相似度排序
        usort($matches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return $matches;
    }
    
    public function getCells() {
        return $this->cells;
    }
    
    public function getSize() {
        return count($this->cells);
    }
    
    public function getMaxSize() {
        return $this->maxSize;
    }
    
    public function getDecayRate() {
        return $this->decayRate;
    }
    
    public function getUpdateInterval() {
        return $this->updateInterval;
    }
    
    public function getLastUpdate() {
        return $this->lastUpdate;
    }
    
    public function clear() {
        $this->cells = [];
        $this->lastUpdate = time();
    }
} 