<?php

namespace Core\Immune;

class Algorithm {
    private $antigen;
    private $antibody;
    private $memory;
    private $threshold;
    private $memorySize;
    private $affinityThreshold;
    private $concentrationDecay;
    
    public function __construct($threshold = 0.85, $memorySize = 1000, $affinityThreshold = 0.7, $concentrationDecay = 0.1) {
        $this->antigen = new Antigen();
        $this->antibody = new Antibody();
        $this->memory = new Memory();
        $this->threshold = $threshold;
        $this->memorySize = $memorySize;
        $this->affinityThreshold = $affinityThreshold;
        $this->concentrationDecay = $concentrationDecay;
        
        // 初始化记忆库大小
        $this->memory->setMaxSize($this->memorySize);
    }
    
    public function analyze($data) {
        try {
            // 数据预处理
            $processedData = $this->preprocessData($data);
            
            // 特征提取
            $features = $this->extractFeatures($processedData);
            
            // 抗原匹配
            $matchResult = $this->antigen->match($features);
            
            // 抗体生成
            $antibodies = $this->antibody->generate($matchResult);
            
            // 记忆更新
            $this->memory->update($antibodies);
            
            // 评估结果
            $results = $this->evaluateResult($antibodies);
            
            // 添加安全验证
            $this->validateResults($results);
            
            return $results;
            
        } catch (\Exception $e) {
            throw new \Exception("分析过程发生错误: " . $e->getMessage());
        }
    }
    
    private function preprocessData($data) {
        // 验证输入数据
        if (!is_array($data)) {
            throw new \InvalidArgumentException("输入数据必须是数组");
        }
        
        // 数据清洗
        $cleanedData = array_filter($data, function($item) {
            return !empty($item) && isset($item['features']);
        });
        
        return $cleanedData;
    }
    
    private function extractFeatures($data) {
        $features = [];
        
        foreach ($data as $item) {
            if (isset($item['features'])) {
                $features = array_merge($features, $item['features']);
            }
        }
        
        return $features;
    }
    
    private function evaluateResult($antibodies) {
        if (!$antibodies) {
            return [
                'is_attack' => false,
                'confidence' => 0,
                'details' => ['message' => '未检测到异常']
            ];
        }
        
        $results = [];
        foreach ($antibodies as $antibody) {
            if ($antibody['affinity'] >= $this->affinityThreshold) {
                $results[] = [
                    'is_attack' => true,
                    'confidence' => $antibody['affinity'],
                    'source_ip' => $antibody['features']['source_ip'] ?? 'unknown',
                    'target_ip' => $antibody['features']['target_ip'] ?? 'unknown',
                    'attack_type' => $this->determineAttackType($antibody),
                    'details' => $antibody['features']
                ];
            }
        }
        
        return $results;
    }
    
    private function determineAttackType($antibody) {
        $features = $antibody['features'];
        
        // 基于特征判断攻击类型
        if (isset($features['port_scan'])) {
            return 'PORT_SCAN';
        } elseif (isset($features['dos_attack'])) {
            return 'DOS_ATTACK';
        } elseif (isset($features['sql_injection'])) {
            return 'SQL_INJECTION';
        } else {
            return 'UNKNOWN_ATTACK';
        }
    }
    
    private function validateResults($results) {
        // 验证结果格式
        if (!is_array($results)) {
            throw new \InvalidArgumentException("分析结果必须是数组");
        }
        
        // 验证每个结果
        foreach ($results as $result) {
            if (!isset($result['is_attack']) || !isset($result['confidence'])) {
                throw new \InvalidArgumentException("结果缺少必要字段");
            }
            
            // 验证置信度范围
            if ($result['confidence'] < 0 || $result['confidence'] > 1) {
                throw new \InvalidArgumentException("置信度必须在0到1之间");
            }
        }
    }
} 