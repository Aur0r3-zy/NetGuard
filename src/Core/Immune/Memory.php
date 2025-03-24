<?php

namespace Core\Immune;

class Memory {
    private $memoryCells;
    private $maxSize;
    
    public function __construct() {
        $this->memoryCells = [];
        $this->maxSize = 1000;
    }
    
    public function update($antibodies) {
        if (!$antibodies) {
            return;
        }
        
        // 添加新的记忆细胞
        $this->addMemoryCell($antibodies);
        
        // 维护记忆库大小
        $this->maintainMemorySize();
    }
    
    private function addMemoryCell($antibodies) {
        $memoryCell = [
            'timestamp' => time(),
            'antibodies' => $antibodies,
            'affinity' => $antibodies['affinity']
        ];
        
        $this->memoryCells[] = $memoryCell;
    }
    
    private function maintainMemorySize() {
        if (count($this->memoryCells) > $this->maxSize) {
            // 按亲和度排序
            usort($this->memoryCells, function($a, $b) {
                return $b['affinity'] - $a['affinity'];
            });
            
            // 保留亲和度最高的记忆细胞
            $this->memoryCells = array_slice($this->memoryCells, 0, $this->maxSize);
        }
    }
    
    public function getMemoryCells() {
        return $this->memoryCells;
    }
    
    public function setMaxSize($size) {
        $this->maxSize = $size;
        $this->maintainMemorySize();
    }
} 