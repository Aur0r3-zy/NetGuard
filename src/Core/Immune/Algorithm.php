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
    private $diversityThreshold;
    private $eliteSize;
    private $archiveSize;
    private $archive;
    private $statistics;
    
    public function __construct(
        $threshold = 0.85, 
        $memorySize = 1000, 
        $affinityThreshold = 0.7, 
        $concentrationDecay = 0.1,
        $learningRate = 0.1,
        $maxIterations = 100,
        $populationSize = 100,
        $mutationRate = 0.1,
        $crossoverRate = 0.8,
        $diversityThreshold = 0.3,
        $eliteSize = 5,
        $archiveSize = 50
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
        $this->diversityThreshold = $diversityThreshold;
        $this->eliteSize = $eliteSize;
        $this->archiveSize = $archiveSize;
        $this->archive = [];
        $this->statistics = [
            'iterations' => 0,
            'best_fitness' => 0,
            'avg_fitness' => 0,
            'diversity' => 0,
            'memory_size' => 0
        ];
        
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
            
            // 更新统计信息
            $this->updateStatistics();
            
            return [
                'results' => $results,
                'statistics' => $this->statistics,
                'archive' => $this->archive
            ];
            
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
        
        // 数据标准化
        $normalizedData = $this->normalizeData($cleanedData);
        
        return $normalizedData;
    }
    
    private function normalizeData($data) {
        $normalized = [];
        $featureRanges = $this->calculateFeatureRanges($data);
        
        foreach ($data as $item) {
            $normalizedItem = [
                'features' => [],
                'metadata' => $item['metadata'] ?? []
            ];
            
            foreach ($item['features'] as $feature => $value) {
                if (isset($featureRanges[$feature])) {
                    $range = $featureRanges[$feature];
                    $normalizedItem['features'][$feature] = ($value - $range['min']) / ($range['max'] - $range['min']);
                } else {
                    $normalizedItem['features'][$feature] = $value;
                }
            }
            
            $normalized[] = $normalizedItem;
        }
        
        return $normalized;
    }
    
    private function calculateFeatureRanges($data) {
        $ranges = [];
        
        foreach ($data as $item) {
            foreach ($item['features'] as $feature => $value) {
                if (!isset($ranges[$feature])) {
                    $ranges[$feature] = ['min' => $value, 'max' => $value];
                } else {
                    $ranges[$feature]['min'] = min($ranges[$feature]['min'], $value);
                    $ranges[$feature]['max'] = max($ranges[$feature]['max'], $value);
                }
            }
        }
        
        return $ranges;
    }
    
    private function extractFeatures($data) {
        $features = [];
        
        foreach ($data as $item) {
            if (isset($item['features'])) {
                $features = array_merge($features, $item['features']);
            }
        }
        
        // 特征选择
        $selectedFeatures = $this->selectFeatures($features);
        
        return $selectedFeatures;
    }
    
    private function selectFeatures($features) {
        // 使用信息增益选择特征
        $selected = [];
        $threshold = 0.1;
        
        foreach ($features as $feature => $value) {
            $gain = $this->calculateInformationGain($feature, $features);
            if ($gain > $threshold) {
                $selected[$feature] = $value;
            }
        }
        
        return $selected;
    }
    
    private function calculateInformationGain($feature, $features) {
        // 计算信息增益
        $entropy = $this->calculateEntropy($features);
        $conditionalEntropy = $this->calculateConditionalEntropy($feature, $features);
        
        return $entropy - $conditionalEntropy;
    }
    
    private function calculateEntropy($features) {
        $counts = array_count_values($features);
        $total = count($features);
        $entropy = 0;
        
        foreach ($counts as $count) {
            $probability = $count / $total;
            $entropy -= $probability * \log($probability, 2);
        }
        
        return $entropy;
    }
    
    private function calculateConditionalEntropy($feature, $features) {
        $conditionalEntropy = 0;
        $featureValues = array_unique($features[$feature]);
        
        foreach ($featureValues as $value) {
            $subset = array_filter($features, function($item) use ($feature, $value) {
                return $item[$feature] === $value;
            });
            
            $probability = count($subset) / count($features);
            $entropy = $this->calculateEntropy($subset);
            $conditionalEntropy += $probability * $entropy;
        }
        
        return $conditionalEntropy;
    }
    
    private function generateAndEvolveAntibodies($matchResult) {
        $population = $this->initializePopulation();
        $bestAntibodies = [];
        $noImprovementCount = 0;
        $lastBestFitness = 0;
        
        for ($iteration = 0; $iteration < $this->maxIterations; $iteration++) {
            // 评估适应度
            $fitnessScores = array_map($this->fitnessFunction, $population);
            
            // 更新精英解
            $elites = $this->selectElites($population, $fitnessScores);
            
            // 选择最佳抗体
            $selectedAntibodies = $this->selection($population, $fitnessScores);
            
            // 交叉操作
            $offspring = $this->crossover($selectedAntibodies);
            
            // 变异操作
            $mutatedOffspring = $this->mutation($offspring);
            
            // 更新种群
            $population = array_merge($elites, $mutatedOffspring);
            
            // 记录最佳抗体
            $currentBest = $this->updateBestAntibodies($population, $fitnessScores);
            
            // 更新档案库
            $this->updateArchive($currentBest);
            
            // 检查改进
            $currentBestFitness = max($fitnessScores);
            if ($currentBestFitness <= $lastBestFitness) {
                $noImprovementCount++;
                if ($noImprovementCount >= 10) {
                    // 触发局部搜索
                    $population = $this->localSearch($population);
                    $noImprovementCount = 0;
                }
            } else {
                $noImprovementCount = 0;
            }
            $lastBestFitness = $currentBestFitness;
            
            // 更新统计信息
            $this->updateIterationStatistics($population, $fitnessScores);
        }
        
        return $bestAntibodies;
    }
    
    private function selectElites($population, $fitnessScores) {
        $elites = [];
        arsort($fitnessScores);
        
        $eliteIndices = array_slice(array_keys($fitnessScores), 0, $this->eliteSize);
        foreach ($eliteIndices as $index) {
            $elites[] = $population[$index];
        }
        
        return $elites;
    }
    
    private function localSearch($population) {
        $improved = [];
        
        foreach ($population as $antibody) {
            $neighbors = $this->generateNeighbors($antibody);
            $bestNeighbor = $this->findBestNeighbor($neighbors);
            
            if ($this->calculateFitness($bestNeighbor) > $this->calculateFitness($antibody)) {
                $improved[] = $bestNeighbor;
            } else {
                $improved[] = $antibody;
            }
        }
        
        return $improved;
    }
    
    private function generateNeighbors($antibody) {
        $neighbors = [];
        $neighborCount = 5;
        
        for ($i = 0; $i < $neighborCount; $i++) {
            $neighbor = $this->mutateAntibody($antibody);
            $neighbors[] = $neighbor;
        }
        
        return $neighbors;
    }
    
    private function findBestNeighbor($neighbors) {
        $bestFitness = -1;
        $bestNeighbor = null;
        
        foreach ($neighbors as $neighbor) {
            $fitness = $this->calculateFitness($neighbor);
            if ($fitness > $bestFitness) {
                $bestFitness = $fitness;
                $bestNeighbor = $neighbor;
            }
        }
        
        return $bestNeighbor;
    }
    
    private function updateArchive($antibodies) {
        foreach ($antibodies as $antibody) {
            $this->archive[] = $antibody;
        }
        
        // 按适应度排序
        usort($this->archive, function($a, $b) {
            return $this->calculateFitness($b) - $this->calculateFitness($a);
        });
        
        // 保持档案库大小
        $this->archive = array_slice($this->archive, 0, $this->archiveSize);
    }
    
    private function updateIterationStatistics($population, $fitnessScores) {
        $this->statistics['iterations']++;
        $this->statistics['best_fitness'] = max($fitnessScores);
        $this->statistics['avg_fitness'] = array_sum($fitnessScores) / count($fitnessScores);
        $this->statistics['diversity'] = $this->calculateDiversity($population);
        $this->statistics['memory_size'] = $this->memory->getSize();
    }
    
    private function calculateDiversity($population) {
        $diversity = 0;
        $count = count($population);
        
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $diversity += $this->calculateHammingDistance(
                    $population[$i]['features'],
                    $population[$j]['features']
                );
            }
        }
        
        return $diversity / ($count * ($count - 1) / 2);
    }
    
    private function calculateHammingDistance($features1, $features2) {
        $distance = 0;
        
        foreach ($features1 as $key => $value) {
            if (isset($features2[$key]) && $features2[$key] !== $value) {
                $distance++;
            }
        }
        
        return $distance;
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
    
    private function updateStatistics() {
        $this->statistics['iterations']++;
        $this->statistics['memory_size'] = $this->memory->getSize();
        $this->statistics['archive_size'] = count($this->archive);
    }
} 