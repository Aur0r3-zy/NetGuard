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
    private $learningRate;
    private $maxIterations;
    private $populationSize;
    private $mutationRate;
    private $crossoverRate;
    private $fitnessFunction;
    
    public function __construct(
        $threshold = 0.85, 
        $memorySize = 1000, 
        $affinityThreshold = 0.7, 
        $concentrationDecay = 0.1,
        $learningRate = 0.1,
        $maxIterations = 100,
        $populationSize = 100,
        $mutationRate = 0.1,
        $crossoverRate = 0.8
    ) {
        $this->antigen = new Antigen();
        $this->antibody = new Antibody();
        $this->memory = new Memory();
        $this->threshold = $threshold;
        $this->memorySize = $memorySize;
        $this->affinityThreshold = $affinityThreshold;
        $this->concentrationDecay = $concentrationDecay;
        $this->learningRate = $learningRate;
        $this->maxIterations = $maxIterations;
        $this->populationSize = $populationSize;
        $this->mutationRate = $mutationRate;
        $this->crossoverRate = $crossoverRate;
        
        // 初始化记忆库大小
        $this->memory->setMaxSize($this->memorySize);
        
        // 初始化适应度函数
        $this->fitnessFunction = function($antibody) {
            return $this->calculateFitness($antibody);
        };
    }
    
    public function analyze($data) {
        try {
            // 数据预处理
            $processedData = $this->preprocessData($data);
            
            // 特征提取
            $features = $this->extractFeatures($processedData);
            
            // 抗原匹配
            $matchResult = $this->antigen->match($features);
            
            // 抗体生成和进化
            $antibodies = $this->generateAndEvolveAntibodies($matchResult);
            
            // 记忆更新
            $this->memory->update($antibodies);
            
            // 评估结果
            $results = $this->evaluateResult($antibodies);
            
            // 添加安全验证
            $this->validateResults($results);
            
            // 性能优化
            $this->optimizeMemory();
            
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
    
    private function generateAndEvolveAntibodies($matchResult) {
        $population = $this->initializePopulation();
        $bestAntibodies = [];
        
        for ($iteration = 0; $iteration < $this->maxIterations; $iteration++) {
            // 评估适应度
            $fitnessScores = array_map($this->fitnessFunction, $population);
            
            // 选择最佳抗体
            $selectedAntibodies = $this->selection($population, $fitnessScores);
            
            // 交叉操作
            $offspring = $this->crossover($selectedAntibodies);
            
            // 变异操作
            $mutatedOffspring = $this->mutation($offspring);
            
            // 更新种群
            $population = array_merge($selectedAntibodies, $mutatedOffspring);
            
            // 记录最佳抗体
            $bestAntibodies = $this->updateBestAntibodies($population, $fitnessScores);
        }
        
        return $bestAntibodies;
    }
    
    private function initializePopulation() {
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $population[] = $this->antibody->generateRandom();
        }
        return $population;
    }
    
    private function selection($population, $fitnessScores) {
        // 轮盘赌选择
        $totalFitness = array_sum($fitnessScores);
        $selected = [];
        
        for ($i = 0; $i < count($population) / 2; $i++) {
            $random = mt_rand() / mt_getrandmax() * $totalFitness;
            $sum = 0;
            
            foreach ($fitnessScores as $index => $fitness) {
                $sum += $fitness;
                if ($sum >= $random) {
                    $selected[] = $population[$index];
                    break;
                }
            }
        }
        
        return $selected;
    }
    
    private function crossover($population) {
        $offspring = [];
        $size = count($population);
        
        for ($i = 0; $i < $size; $i += 2) {
            if (mt_rand() / mt_getrandmax() < $this->crossoverRate) {
                $parent1 = $population[$i];
                $parent2 = $population[($i + 1) % $size];
                
                // 单点交叉
                $crossoverPoint = mt_rand(0, count($parent1['features']) - 1);
                
                $child1 = $this->createChild($parent1, $parent2, $crossoverPoint);
                $child2 = $this->createChild($parent2, $parent1, $crossoverPoint);
                
                $offspring[] = $child1;
                $offspring[] = $child2;
            }
        }
        
        return $offspring;
    }
    
    private function createChild($parent1, $parent2, $crossoverPoint) {
        $child = [
            'features' => array_merge(
                array_slice($parent1['features'], 0, $crossoverPoint),
                array_slice($parent2['features'], $crossoverPoint)
            ),
            'affinity' => 0
        ];
        
        return $child;
    }
    
    private function mutation($population) {
        $mutated = [];
        
        foreach ($population as $antibody) {
            if (mt_rand() / mt_getrandmax() < $this->mutationRate) {
                $mutated[] = $this->mutateAntibody($antibody);
            } else {
                $mutated[] = $antibody;
            }
        }
        
        return $mutated;
    }
    
    private function mutateAntibody($antibody) {
        $mutated = $antibody;
        $featureCount = count($antibody['features']);
        
        // 随机选择特征进行变异
        $mutationPoints = mt_rand(1, $featureCount);
        for ($i = 0; $i < $mutationPoints; $i++) {
            $point = mt_rand(0, $featureCount - 1);
            $mutated['features'][$point] = $this->generateRandomFeature();
        }
        
        return $mutated;
    }
    
    private function generateRandomFeature() {
        // 生成随机特征值
        return mt_rand(0, 1);
    }
    
    private function calculateFitness($antibody) {
        // 计算抗体与抗原的亲和度
        $affinity = $this->antigen->calculateAffinity($antibody['features']);
        
        // 考虑记忆库中的相似抗体
        $memoryAffinity = $this->memory->calculateMemoryAffinity($antibody);
        
        // 综合评分
        return $affinity * (1 - $memoryAffinity);
    }
    
    private function updateBestAntibodies($population, $fitnessScores) {
        $bestAntibodies = [];
        $bestCount = min(10, count($population));
        
        // 获取最佳抗体
        arsort($fitnessScores);
        $bestIndices = array_slice(array_keys($fitnessScores), 0, $bestCount);
        
        foreach ($bestIndices as $index) {
            $bestAntibodies[] = $population[$index];
        }
        
        return $bestAntibodies;
    }
    
    private function optimizeMemory() {
        // 清理低亲和度记忆
        $this->memory->cleanup($this->affinityThreshold);
        
        // 合并相似记忆
        $this->memory->mergeSimilar();
        
        // 更新记忆库大小
        if ($this->memory->getSize() > $this->memorySize) {
            $this->memory->trim($this->memorySize);
        }
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