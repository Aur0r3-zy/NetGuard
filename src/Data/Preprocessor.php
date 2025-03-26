<?php

namespace Data;

/**
 * 数据预处理器类
 * 用于对原始数据进行预处理
 */
class Preprocessor {
    private array $config;
    private array $stats;
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'remove_duplicates' => true,
            'handle_missing' => 'mean',
            'handle_outliers' => true,
            'outlier_threshold' => 3,
            'normalize' => true,
            'normalization_method' => 'zscore',
            'encode_categorical' => true,
            'encode_method' => 'onehot',
            'feature_selection' => true,
            'feature_threshold' => 0.1,
            'dimensionality_reduction' => false,
            'reduction_method' => 'pca',
            'reduction_components' => 2,
            'time_series' => false,
            'window_size' => 10,
            'aggregation' => 'mean',
            'smoothing' => false,
            'smoothing_window' => 3,
            'smoothing_method' => 'moving_average'
        ], $config);
        
        $this->stats = [];
    }
    
    /**
     * 预处理数据
     * @param array $data 原始数据
     * @return array 预处理后的数据
     */
    public function preprocess(array $data): array {
        if (empty($data)) {
            throw new \InvalidArgumentException('输入数据不能为空');
        }
        
        // 计算统计信息
        $this->calculateStats($data);
        
        // 移除重复数据
        if ($this->config['remove_duplicates']) {
            $data = $this->removeDuplicates($data);
        }
        
        // 处理缺失值
        $data = $this->handleMissingValues($data);
        
        // 处理异常值
        if ($this->config['handle_outliers']) {
            $data = $this->handleOutliers($data);
        }
        
        // 特征选择
        if ($this->config['feature_selection']) {
            $data = $this->selectFeatures($data);
        }
        
        // 降维
        if ($this->config['dimensionality_reduction']) {
            $data = $this->reduceDimensionality($data);
        }
        
        // 时间序列处理
        if ($this->config['time_series']) {
            $data = $this->processTimeSeries($data);
        }
        
        // 平滑处理
        if ($this->config['smoothing']) {
            $data = $this->smoothData($data);
        }
        
        // 标准化
        if ($this->config['normalize']) {
            $normalizer = new Normalizer([
                'method' => $this->config['normalization_method']
            ]);
            $data = $normalizer->normalize($data);
        }
        
        // 分类特征编码
        if ($this->config['encode_categorical']) {
            $data = $this->encodeCategoricalFeatures($data);
        }
        
        return $data;
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
            'iqr' => $this->calculateIQR($values),
            'skewness' => $this->calculateSkewness($values),
            'kurtosis' => $this->calculateKurtosis($values)
        ];
    }
    
    /**
     * 移除重复数据
     * @param array $data 原始数据
     * @return array 去重后的数据
     */
    private function removeDuplicates(array $data): array {
        $unique = [];
        $seen = [];
        
        foreach ($data as $item) {
            $key = md5(json_encode($item));
            if (!isset($seen[$key])) {
                $unique[] = $item;
                $seen[$key] = true;
            }
        }
        
        return $unique;
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
                $item['value'] = $this->stats['mean'];
            }
        }
        
        return $data;
    }
    
    /**
     * 特征选择
     * @param array $data 原始数据
     * @return array 选择后的数据
     */
    private function selectFeatures(array $data): array {
        $threshold = $this->config['feature_threshold'];
        $selectedFeatures = [];
        
        // 计算特征重要性
        $importance = $this->calculateFeatureImportance($data);
        
        // 选择重要特征
        foreach ($importance as $feature => $score) {
            if ($score >= $threshold) {
                $selectedFeatures[] = $feature;
            }
        }
        
        // 只保留选中的特征
        return array_map(function($item) use ($selectedFeatures) {
            $filtered = [];
            foreach ($selectedFeatures as $feature) {
                if (isset($item[$feature])) {
                    $filtered[$feature] = $item[$feature];
                }
            }
            return $filtered;
        }, $data);
    }
    
    /**
     * 降维处理
     * @param array $data 原始数据
     * @return array 降维后的数据
     */
    private function reduceDimensionality(array $data): array {
        switch ($this->config['reduction_method']) {
            case 'pca':
                return $this->pcaReduction($data);
            case 't-sne':
                return $this->tSNEReduction($data);
            default:
                throw new \InvalidArgumentException('不支持的降维方法');
        }
    }
    
    /**
     * PCA降维
     * @param array $data 原始数据
     * @return array 降维后的数据
     */
    private function pcaReduction(array $data): array {
        // 实现PCA降维逻辑
        return $data;
    }
    
    /**
     * t-SNE降维
     * @param array $data 原始数据
     * @return array 降维后的数据
     */
    private function tSNEReduction(array $data): array {
        // 实现t-SNE降维逻辑
        return $data;
    }
    
    /**
     * 时间序列处理
     * @param array $data 原始数据
     * @return array 处理后的数据
     */
    private function processTimeSeries(array $data): array {
        $windowSize = $this->config['window_size'];
        $aggregation = $this->config['aggregation'];
        
        $processed = [];
        $window = [];
        
        foreach ($data as $item) {
            $window[] = $item['value'];
            
            if (count($window) >= $windowSize) {
                $processed[] = [
                    'timestamp' => $item['timestamp'],
                    'value' => $this->aggregateWindow($window, $aggregation)
                ];
                array_shift($window);
            }
        }
        
        return $processed;
    }
    
    /**
     * 平滑处理
     * @param array $data 原始数据
     * @return array 平滑后的数据
     */
    private function smoothData(array $data): array {
        $windowSize = $this->config['smoothing_window'];
        $method = $this->config['smoothing_method'];
        
        switch ($method) {
            case 'moving_average':
                return $this->movingAverageSmoothing($data, $windowSize);
            case 'exponential':
                return $this->exponentialSmoothing($data, $windowSize);
            default:
                throw new \InvalidArgumentException('不支持的平滑方法');
        }
    }
    
    /**
     * 移动平均平滑
     * @param array $data 原始数据
     * @param int $windowSize 窗口大小
     * @return array 平滑后的数据
     */
    private function movingAverageSmoothing(array $data, int $windowSize): array {
        $smoothed = [];
        $window = [];
        
        foreach ($data as $item) {
            $window[] = $item['value'];
            
            if (count($window) >= $windowSize) {
                $smoothed[] = [
                    'timestamp' => $item['timestamp'],
                    'value' => array_sum($window) / $windowSize
                ];
                array_shift($window);
            }
        }
        
        return $smoothed;
    }
    
    /**
     * 指数平滑
     * @param array $data 原始数据
     * @param int $windowSize 窗口大小
     * @return array 平滑后的数据
     */
    private function exponentialSmoothing(array $data, int $windowSize): array {
        $alpha = 2 / ($windowSize + 1);
        $smoothed = [];
        $prevValue = $data[0]['value'];
        
        foreach ($data as $item) {
            $currentValue = $alpha * $item['value'] + (1 - $alpha) * $prevValue;
            $smoothed[] = [
                'timestamp' => $item['timestamp'],
                'value' => $currentValue
            ];
            $prevValue = $currentValue;
        }
        
        return $smoothed;
    }
    
    /**
     * 分类特征编码
     * @param array $data 原始数据
     * @return array 编码后的数据
     */
    private function encodeCategoricalFeatures(array $data): array {
        switch ($this->config['encode_method']) {
            case 'onehot':
                return $this->oneHotEncoding($data);
            case 'label':
                return $this->labelEncoding($data);
            default:
                throw new \InvalidArgumentException('不支持的编码方法');
        }
    }
    
    /**
     * One-Hot编码
     * @param array $data 原始数据
     * @return array 编码后的数据
     */
    private function oneHotEncoding(array $data): array {
        $encoded = [];
        $categories = [];
        
        // 收集所有类别
        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                if (is_string($value)) {
                    $categories[$key][] = $value;
                }
            }
        }
        
        // 对每个类别进行编码
        foreach ($data as $item) {
            $encodedItem = [];
            foreach ($categories as $key => $values) {
                $uniqueValues = array_unique($values);
                foreach ($uniqueValues as $value) {
                    $encodedItem[$key . '_' . $value] = $item[$key] === $value ? 1 : 0;
                }
            }
            $encoded[] = $encodedItem;
        }
        
        return $encoded;
    }
    
    /**
     * 标签编码
     * @param array $data 原始数据
     * @return array 编码后的数据
     */
    private function labelEncoding(array $data): array {
        $encoded = [];
        $categories = [];
        
        // 收集所有类别
        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                if (is_string($value)) {
                    $categories[$key][] = $value;
                }
            }
        }
        
        // 对每个类别进行编码
        foreach ($categories as $key => $values) {
            $uniqueValues = array_unique($values);
            $mapping = array_flip($uniqueValues);
            
            foreach ($data as &$item) {
                if (isset($item[$key])) {
                    $item[$key] = $mapping[$item[$key]];
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 计算特征重要性
     * @param array $data 原始数据
     * @return array 特征重要性
     */
    private function calculateFeatureImportance(array $data): array {
        // 实现特征重要性计算逻辑
        return [];
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
     * 计算偏度
     * @param array $values 数值数组
     * @return float 偏度
     */
    private function calculateSkewness(array $values): float {
        $mean = array_sum($values) / count($values);
        $stdDev = $this->calculateStdDev($values);
        
        if ($stdDev === 0) return 0;
        
        $cubedDiffs = array_map(function($value) use ($mean, $stdDev) {
            return pow(($value - $mean) / $stdDev, 3);
        }, $values);
        
        return array_sum($cubedDiffs) / count($values);
    }
    
    /**
     * 计算峰度
     * @param array $values 数值数组
     * @return float 峰度
     */
    private function calculateKurtosis(array $values): float {
        $mean = array_sum($values) / count($values);
        $stdDev = $this->calculateStdDev($values);
        
        if ($stdDev === 0) return 0;
        
        $fourthPowerDiffs = array_map(function($value) use ($mean, $stdDev) {
            return pow(($value - $mean) / $stdDev, 4);
        }, $values);
        
        return array_sum($fourthPowerDiffs) / count($values);
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
     * 聚合窗口数据
     * @param array $window 窗口数据
     * @param string $method 聚合方法
     * @return float 聚合结果
     */
    private function aggregateWindow(array $window, string $method): float {
        switch ($method) {
            case 'mean':
                return array_sum($window) / count($window);
            case 'median':
                return $this->calculateMedian($window);
            case 'max':
                return max($window);
            case 'min':
                return min($window);
            case 'sum':
                return array_sum($window);
            default:
                throw new \InvalidArgumentException('不支持的聚合方法');
        }
    }
    
    /**
     * 获取统计信息
     * @return array 统计信息
     */
    public function getStats(): array {
        return $this->stats;
    }
} 