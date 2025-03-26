<?php

namespace Data;

/**
 * 数据标准化类
 * 用于对数据进行标准化处理
 */
class Normalizer {
    private array $config;
    private array $stats;
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'method' => 'zscore',
            'handle_missing' => 'mean',
            'handle_outliers' => true,
            'outlier_threshold' => 3,
            'min_max_range' => [0, 1],
            'robust_scaling' => false
        ], $config);
        
        $this->stats = [];
    }
    
    /**
     * 标准化数据
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    public function normalize(array $data): array {
        if (empty($data)) {
            throw new \InvalidArgumentException('输入数据不能为空');
        }
        
        // 计算统计信息
        $this->calculateStats($data);
        
        // 处理缺失值
        $data = $this->handleMissingValues($data);
        
        // 处理异常值
        if ($this->config['handle_outliers']) {
            $data = $this->handleOutliers($data);
        }
        
        // 根据配置的方法进行标准化
        switch ($this->config['method']) {
            case 'zscore':
                return $this->zscoreNormalize($data);
            case 'minmax':
                return $this->minMaxNormalize($data);
            case 'robust':
                return $this->robustNormalize($data);
            case 'decimal':
                return $this->decimalNormalize($data);
            default:
                throw new \InvalidArgumentException('不支持的标准化方法');
        }
    }
    
    /**
     * 计算统计信息
     * @param array $data 原始数据
     */
    private function calculateStats(array $data): void {
        $values = array_column($data, 'value');
        
        $this->stats = [
            'mean' => array_sum($values) / count($values),
            'std_dev' => $this->calculateStdDev($values),
            'min' => min($values),
            'max' => max($values),
            'median' => $this->calculateMedian($values),
            'q1' => $this->calculateQuartile($values, 0.25),
            'q3' => $this->calculateQuartile($values, 0.75),
            'iqr' => $this->calculateIQR($values)
        ];
    }
    
    /**
     * 处理缺失值
     * @param array $data 原始数据
     * @return array 处理后的数据
     */
    private function handleMissingValues(array $data): array {
        foreach ($data as &$item) {
            if (!isset($item['value']) || is_null($item['value'])) {
                switch ($this->config['handle_missing']) {
                    case 'mean':
                        $item['value'] = $this->stats['mean'];
                        break;
                    case 'median':
                        $item['value'] = $this->stats['median'];
                        break;
                    case 'mode':
                        $item['value'] = $this->calculateMode(array_column($data, 'value'));
                        break;
                    case 'zero':
                        $item['value'] = 0;
                        break;
                    default:
                        throw new \InvalidArgumentException('不支持的缺失值处理方法');
                }
            }
        }
        return $data;
    }
    
    /**
     * 处理异常值
     * @param array $data 原始数据
     * @return array 处理后的数据
     */
    private function handleOutliers(array $data): array {
        $threshold = $this->config['outlier_threshold'];
        
        foreach ($data as &$item) {
            if ($this->isOutlier($item['value'], $threshold)) {
                if ($this->config['robust_scaling']) {
                    $item['value'] = $this->winsorize($item['value']);
                } else {
                    $item['value'] = $this->stats['mean'];
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Z-score标准化
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    private function zscoreNormalize(array $data): array {
        if ($this->stats['std_dev'] === 0) {
            return array_fill(0, count($data), 0);
        }
        
        foreach ($data as &$item) {
            $item['value'] = ($item['value'] - $this->stats['mean']) / $this->stats['std_dev'];
        }
        
        return $data;
    }
    
    /**
     * Min-Max标准化
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    private function minMaxNormalize(array $data): array {
        $range = $this->stats['max'] - $this->stats['min'];
        if ($range === 0) {
            return array_fill(0, count($data), 0);
        }
        
        $min = $this->config['min_max_range'][0];
        $max = $this->config['min_max_range'][1];
        
        foreach ($data as &$item) {
            $item['value'] = $min + ($max - $min) * 
                ($item['value'] - $this->stats['min']) / $range;
        }
        
        return $data;
    }
    
    /**
     * Robust标准化
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    private function robustNormalize(array $data): array {
        if ($this->stats['iqr'] === 0) {
            return array_fill(0, count($data), 0);
        }
        
        foreach ($data as &$item) {
            $item['value'] = ($item['value'] - $this->stats['median']) / $this->stats['iqr'];
        }
        
        return $data;
    }
    
    /**
     * Decimal标准化
     * @param array $data 原始数据
     * @return array 标准化后的数据
     */
    private function decimalNormalize(array $data): array {
        $maxAbs = max(array_map('abs', array_column($data, 'value')));
        if ($maxAbs === 0) {
            return array_fill(0, count($data), 0);
        }
        
        foreach ($data as &$item) {
            $item['value'] = $item['value'] / $maxAbs;
        }
        
        return $data;
    }
    
    /**
     * 计算标准差
     * @param array $values 数值数组
     * @return float 标准差
     */
    private function calculateStdDev(array $values): float {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        return sqrt(array_sum($squaredDiffs) / count($values));
    }
    
    /**
     * 计算中位数
     * @param array $values 数值数组
     * @return float 中位数
     */
    private function calculateMedian(array $values): float {
        $count = count($values);
        if ($count === 0) return 0;
        
        sort($values);
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        return $values[$middle];
    }
    
    /**
     * 计算四分位数
     * @param array $values 数值数组
     * @param float $percentile 百分位数
     * @return float 四分位数
     */
    private function calculateQuartile(array $values, float $percentile): float {
        $count = count($values);
        if ($count === 0) return 0;
        
        sort($values);
        $index = ($count - 1) * $percentile;
        $lowerIndex = floor($index);
        $upperIndex = ceil($index);
        
        if ($lowerIndex === $upperIndex) {
            return $values[$lowerIndex];
        }
        
        $lowerValue = $values[$lowerIndex];
        $upperValue = $values[$upperIndex];
        $fraction = $index - $lowerIndex;
        
        return $lowerValue + $fraction * ($upperValue - $lowerValue);
    }
    
    /**
     * 计算四分位距
     * @param array $values 数值数组
     * @return float 四分位距
     */
    private function calculateIQR(array $values): float {
        return $this->stats['q3'] - $this->stats['q1'];
    }
    
    /**
     * 计算众数
     * @param array $values 数值数组
     * @return float 众数
     */
    private function calculateMode(array $values): float {
        $counts = array_count_values($values);
        arsort($counts);
        return key($counts);
    }
    
    /**
     * 判断是否为异常值
     * @param float $value 数值
     * @param float $threshold 阈值
     * @return bool 是否为异常值
     */
    private function isOutlier(float $value, float $threshold): bool {
        $zscore = abs(($value - $this->stats['mean']) / $this->stats['std_dev']);
        return $zscore > $threshold;
    }
    
    /**
     * Winsorize处理
     * @param float $value 数值
     * @return float 处理后的数值
     */
    private function winsorize(float $value): float {
        $lowerBound = $this->stats['q1'] - 1.5 * $this->stats['iqr'];
        $upperBound = $this->stats['q3'] + 1.5 * $this->stats['iqr'];
        
        if ($value < $lowerBound) {
            return $lowerBound;
        }
        if ($value > $upperBound) {
            return $upperBound;
        }
        
        return $value;
    }
    
    /**
     * 获取统计信息
     * @return array 统计信息
     */
    public function getStats(): array {
        return $this->stats;
    }
} 