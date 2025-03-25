<?php

namespace Core\Monitor;

class AttackMonitor {
    private $trafficAnalyzer;
    private $algorithm;
    private $logger;
    private $config;
    private $cache;
    private $db;
    private $thresholds;
    private $patterns;
    private $blacklist;
    private $whitelist;
    
    public function __construct($trafficAnalyzer, $algorithm, $logger, $db, $cache = null) {
        $this->trafficAnalyzer = $trafficAnalyzer;
        $this->algorithm = $algorithm;
        $this->logger = $logger;
        $this->db = $db;
        $this->cache = $cache;
        $this->config = $this->loadConfig();
        $this->thresholds = $this->config['thresholds'] ?? [];
        $this->patterns = $this->config['patterns'] ?? [];
        $this->blacklist = [];
        $this->whitelist = [];
        
        // 加载黑名单和白名单
        $this->loadLists();
    }
    
    private function loadConfig() {
        $configFile = __DIR__ . '/../../config/monitor.php';
        return file_exists($configFile) ? require $configFile : [];
    }
    
    private function loadLists() {
        try {
            // 从数据库加载黑名单
            $stmt = $this->db->prepare("SELECT ip_address FROM blacklist WHERE active = 1");
            $stmt->execute();
            $this->blacklist = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // 从数据库加载白名单
            $stmt = $this->db->prepare("SELECT ip_address FROM whitelist WHERE active = 1");
            $stmt->execute();
            $this->whitelist = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->logger->error('加载黑白名单失败：' . $e->getMessage());
        }
    }
    
    public function monitorTraffic() {
        try {
            // 实时流量监测
            $packets = $this->trafficAnalyzer->capturePackets();
            
            // 异常行为识别
            $anomalies = $this->detectAnomalies($packets);
            
            // 数据包分析
            $analysis = $this->analyzePackets($packets);
            
            // 攻击源定位
            $sources = $this->locateAttackSources($packets);
            
            // 攻击模式识别
            $patterns = $this->identifyAttackPatterns($packets);
            
            // 风险评估
            $riskAssessment = $this->assessRisk($packets, $anomalies, $patterns);
            
            // 保存监控数据
            $this->saveMonitoringData([
                'packets' => $packets,
                'anomalies' => $anomalies,
                'analysis' => $analysis,
                'sources' => $sources,
                'patterns' => $patterns,
                'risk_assessment' => $riskAssessment
            ]);
            
            return [
                'status' => 'success',
                'data' => [
                    'traffic' => $packets,
                    'anomalies' => $anomalies,
                    'analysis' => $analysis,
                    'sources' => $sources,
                    'patterns' => $patterns,
                    'risk_assessment' => $riskAssessment
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量监测失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量监测失败：' . $e->getMessage()
            ];
        }
    }
    
    private function detectAnomalies($packets) {
        $anomalies = [];
        
        foreach ($packets as $packet) {
            // 检查黑名单
            if ($this->isBlacklisted($packet)) {
                $anomalies[] = [
                    'type' => 'blacklist_match',
                    'packet' => $packet,
                    'timestamp' => time(),
                    'severity' => 'high'
                ];
                continue;
            }
            
            // 检查白名单
            if ($this->isWhitelisted($packet)) {
                continue;
            }
            
            // 检测异常流量模式
            if ($this->isAnomalous($packet)) {
                $anomalies[] = [
                    'type' => 'traffic_anomaly',
                    'packet' => $packet,
                    'timestamp' => time(),
                    'severity' => $this->calculateSeverity($packet)
                ];
            }
            
            // 检测协议异常
            if ($this->isProtocolAnomaly($packet)) {
                $anomalies[] = [
                    'type' => 'protocol_anomaly',
                    'packet' => $packet,
                    'timestamp' => time(),
                    'severity' => 'medium'
                ];
            }
            
            // 检测端口扫描
            if ($this->isPortScan($packet)) {
                $anomalies[] = [
                    'type' => 'port_scan',
                    'packet' => $packet,
                    'timestamp' => time(),
                    'severity' => 'high'
                ];
            }
        }
        
        return $anomalies;
    }
    
    private function analyzePackets($packets) {
        $analysis = [
            'total_packets' => count($packets),
            'protocols' => [],
            'sizes' => [],
            'timestamps' => [],
            'ports' => [],
            'ip_ranges' => [],
            'bandwidth' => 0,
            'packet_rate' => 0
        ];
        
        $startTime = microtime(true);
        $totalSize = 0;
        
        foreach ($packets as $packet) {
            // 协议统计
            $protocol = $packet['protocol'] ?? 'unknown';
            $analysis['protocols'][$protocol] = ($analysis['protocols'][$protocol] ?? 0) + 1;
            
            // 数据包大小统计
            $size = $packet['size'] ?? 0;
            $analysis['sizes'][] = $size;
            $totalSize += $size;
            
            // 时间戳记录
            $analysis['timestamps'][] = $packet['timestamp'] ?? time();
            
            // 端口统计
            if (isset($packet['source_port'])) {
                $analysis['ports']['source'][$packet['source_port']] = ($analysis['ports']['source'][$packet['source_port']] ?? 0) + 1;
            }
            if (isset($packet['destination_port'])) {
                $analysis['ports']['destination'][$packet['destination_port']] = ($analysis['ports']['destination'][$packet['destination_port']] ?? 0) + 1;
            }
            
            // IP范围统计
            if (isset($packet['source_ip'])) {
                $ipRange = $this->getIpRange($packet['source_ip']);
                $analysis['ip_ranges'][$ipRange] = ($analysis['ip_ranges'][$ipRange] ?? 0) + 1;
            }
        }
        
        // 计算带宽和包率
        $duration = microtime(true) - $startTime;
        $analysis['bandwidth'] = $totalSize / $duration; // bytes per second
        $analysis['packet_rate'] = count($packets) / $duration; // packets per second
        
        // 计算统计信息
        $analysis['stats'] = [
            'avg_size' => count($analysis['sizes']) > 0 ? array_sum($analysis['sizes']) / count($analysis['sizes']) : 0,
            'max_size' => count($analysis['sizes']) > 0 ? max($analysis['sizes']) : 0,
            'min_size' => count($analysis['sizes']) > 0 ? min($analysis['sizes']) : 0,
            'std_dev' => $this->calculateStandardDeviation($analysis['sizes'])
        ];
        
        return $analysis;
    }
    
    private function locateAttackSources($packets) {
        $sources = [];
        
        foreach ($packets as $packet) {
            if (isset($packet['source_ip'])) {
                $ip = $packet['source_ip'];
                
                if (!isset($sources[$ip])) {
                    $sources[$ip] = [
                        'count' => 0,
                        'last_seen' => time(),
                        'protocols' => [],
                        'ports' => [],
                        'total_size' => 0,
                        'anomaly_count' => 0,
                        'risk_score' => 0
                    ];
                }
                
                $sources[$ip]['count']++;
                $sources[$ip]['last_seen'] = time();
                $sources[$ip]['protocols'] = array_unique(array_merge(
                    $sources[$ip]['protocols'],
                    [$packet['protocol'] ?? 'unknown']
                ));
                
                if (isset($packet['source_port'])) {
                    $sources[$ip]['ports'][] = $packet['source_port'];
                }
                
                $sources[$ip]['total_size'] += $packet['size'] ?? 0;
                
                // 计算风险分数
                $sources[$ip]['risk_score'] = $this->calculateSourceRiskScore($sources[$ip]);
            }
        }
        
        // 按风险分数排序
        uasort($sources, function($a, $b) {
            return $b['risk_score'] - $a['risk_score'];
        });
        
        return $sources;
    }
    
    private function identifyAttackPatterns($packets) {
        $patterns = [];
        
        foreach ($this->patterns as $pattern) {
            $matches = $this->matchPattern($packets, $pattern);
            if ($matches) {
                $patterns[] = [
                    'type' => $pattern['type'],
                    'description' => $pattern['description'],
                    'matches' => $matches,
                    'severity' => $pattern['severity'],
                    'timestamp' => time()
                ];
            }
        }
        
        return $patterns;
    }
    
    private function assessRisk($packets, $anomalies, $patterns) {
        $riskScore = 0;
        $riskFactors = [];
        
        // 基于异常数量评估风险
        $anomalyRisk = count($anomalies) * 10;
        $riskScore += $anomalyRisk;
        $riskFactors['anomalies'] = $anomalyRisk;
        
        // 基于攻击模式评估风险
        $patternRisk = 0;
        foreach ($patterns as $pattern) {
            $patternRisk += $this->getPatternRiskScore($pattern);
        }
        $riskScore += $patternRisk;
        $riskFactors['patterns'] = $patternRisk;
        
        // 基于流量特征评估风险
        $trafficRisk = $this->assessTrafficRisk($packets);
        $riskScore += $trafficRisk;
        $riskFactors['traffic'] = $trafficRisk;
        
        // 计算总体风险等级
        $riskLevel = $this->calculateRiskLevel($riskScore);
        
        return [
            'score' => $riskScore,
            'level' => $riskLevel,
            'factors' => $riskFactors,
            'timestamp' => time()
        ];
    }
    
    private function saveMonitoringData($data) {
        try {
            // 保存到数据库
            $stmt = $this->db->prepare("
                INSERT INTO monitoring_data (
                    packets, anomalies, analysis, sources,
                    patterns, risk_assessment, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                json_encode($data['packets']),
                json_encode($data['anomalies']),
                json_encode($data['analysis']),
                json_encode($data['sources']),
                json_encode($data['patterns']),
                json_encode($data['risk_assessment']),
                time()
            ]);
            
            // 更新缓存
            if ($this->cache) {
                $this->cache->set('monitoring_data', $data, 300); // 缓存5分钟
            }
        } catch (\Exception $e) {
            $this->logger->error('保存监控数据失败：' . $e->getMessage());
        }
    }
    
    private function isBlacklisted($packet) {
        return isset($packet['source_ip']) && in_array($packet['source_ip'], $this->blacklist);
    }
    
    private function isWhitelisted($packet) {
        return isset($packet['source_ip']) && in_array($packet['source_ip'], $this->whitelist);
    }
    
    private function isAnomalous($packet) {
        // 检查数据包大小
        if (isset($packet['size']) && $packet['size'] > ($this->thresholds['max_packet_size'] ?? 1500)) {
            return true;
        }
        
        // 检查协议异常
        if (isset($packet['protocol']) && !in_array($packet['protocol'], $this->thresholds['allowed_protocols'] ?? [])) {
            return true;
        }
        
        // 检查端口异常
        if (isset($packet['destination_port']) && !in_array($packet['destination_port'], $this->thresholds['allowed_ports'] ?? [])) {
            return true;
        }
        
        return false;
    }
    
    private function isProtocolAnomaly($packet) {
        if (!isset($packet['protocol'])) {
            return false;
        }
        
        // 检查协议字段异常
        $protocol = $packet['protocol'];
        $allowedProtocols = $this->thresholds['allowed_protocols'] ?? [];
        
        return !in_array($protocol, $allowedProtocols);
    }
    
    private function isPortScan($packet) {
        static $portScanAttempts = [];
        
        if (!isset($packet['source_ip']) || !isset($packet['destination_port'])) {
            return false;
        }
        
        $ip = $packet['source_ip'];
        $port = $packet['destination_port'];
        
        if (!isset($portScanAttempts[$ip])) {
            $portScanAttempts[$ip] = [
                'ports' => [],
                'start_time' => time()
            ];
        }
        
        $portScanAttempts[$ip]['ports'][] = $port;
        
        // 检查是否在短时间内尝试访问多个不同端口
        $timeWindow = 60; // 1分钟
        $maxPorts = 10; // 最大端口数
        
        if (time() - $portScanAttempts[$ip]['start_time'] <= $timeWindow) {
            if (count(array_unique($portScanAttempts[$ip]['ports'])) > $maxPorts) {
                return true;
            }
        } else {
            // 重置计数器
            $portScanAttempts[$ip] = [
                'ports' => [$port],
                'start_time' => time()
            ];
        }
        
        return false;
    }
    
    private function calculateSeverity($packet) {
        $severity = 'low';
        
        // 基于数据包大小判断严重程度
        if (isset($packet['size']) && $packet['size'] > ($this->thresholds['critical_packet_size'] ?? 5000)) {
            $severity = 'high';
        }
        
        // 基于协议判断严重程度
        if (isset($packet['protocol']) && in_array($packet['protocol'], $this->thresholds['critical_protocols'] ?? [])) {
            $severity = 'high';
        }
        
        // 基于端口判断严重程度
        if (isset($packet['destination_port']) && in_array($packet['destination_port'], $this->thresholds['critical_ports'] ?? [])) {
            $severity = 'high';
        }
        
        return $severity;
    }
    
    private function getIpRange($ip) {
        $parts = explode('.', $ip);
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
    }
    
    private function calculateStandardDeviation($values) {
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return sqrt(array_sum($squaredDiffs) / (count($values) - 1));
    }
    
    private function calculateSourceRiskScore($source) {
        $score = 0;
        
        // 基于数据包数量
        $score += min($source['count'] * 0.1, 20);
        
        // 基于协议数量
        $score += count($source['protocols']) * 5;
        
        // 基于端口数量
        $score += count(array_unique($source['ports'])) * 2;
        
        // 基于总流量大小
        $score += min($source['total_size'] / 1000000, 10); // 每MB增加1分，最多10分
        
        // 基于异常数量
        $score += $source['anomaly_count'] * 10;
        
        return min($score, 100); // 最高100分
    }
    
    private function matchPattern($packets, $pattern) {
        $matches = [];
        
        foreach ($packets as $packet) {
            if ($this->matchesPattern($packet, $pattern)) {
                $matches[] = $packet;
            }
        }
        
        return $matches;
    }
    
    private function matchesPattern($packet, $pattern) {
        // 实现模式匹配逻辑
        foreach ($pattern['conditions'] as $condition) {
            if (!$this->matchesCondition($packet, $condition)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function matchesCondition($packet, $condition) {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        if (!isset($packet[$field])) {
            return false;
        }
        
        switch ($operator) {
            case 'equals':
                return $packet[$field] === $value;
            case 'contains':
                return strpos($packet[$field], $value) !== false;
            case 'greater_than':
                return $packet[$field] > $value;
            case 'less_than':
                return $packet[$field] < $value;
            case 'in_range':
                return $value[0] <= $packet[$field] && $packet[$field] <= $value[1];
            default:
                return false;
        }
    }
    
    private function getPatternRiskScore($pattern) {
        $severityScores = [
            'low' => 5,
            'medium' => 15,
            'high' => 30
        ];
        
        return $severityScores[$pattern['severity']] ?? 5;
    }
    
    private function assessTrafficRisk($packets) {
        $risk = 0;
        
        // 检查流量突发
        $bandwidth = $this->calculateBandwidth($packets);
        if ($bandwidth > ($this->thresholds['max_bandwidth'] ?? 1000000)) { // 1Mbps
            $risk += 20;
        }
        
        // 检查包率
        $packetRate = count($packets) / 60; // 每分钟包数
        if ($packetRate > ($this->thresholds['max_packet_rate'] ?? 1000)) {
            $risk += 15;
        }
        
        // 检查协议分布
        $protocols = $this->getProtocolDistribution($packets);
        foreach ($protocols as $protocol => $count) {
            if ($count > ($this->thresholds['max_protocol_count'] ?? 100)) {
                $risk += 10;
            }
        }
        
        return $risk;
    }
    
    private function calculateBandwidth($packets) {
        $totalSize = 0;
        $startTime = null;
        $endTime = null;
        
        foreach ($packets as $packet) {
            $totalSize += $packet['size'] ?? 0;
            
            if ($startTime === null || $packet['timestamp'] < $startTime) {
                $startTime = $packet['timestamp'];
            }
            
            if ($endTime === null || $packet['timestamp'] > $endTime) {
                $endTime = $packet['timestamp'];
            }
        }
        
        $duration = $endTime - $startTime;
        return $duration > 0 ? ($totalSize * 8) / $duration : 0; // bits per second
    }
    
    private function getProtocolDistribution($packets) {
        $protocols = [];
        
        foreach ($packets as $packet) {
            $protocol = $packet['protocol'] ?? 'unknown';
            $protocols[$protocol] = ($protocols[$protocol] ?? 0) + 1;
        }
        
        return $protocols;
    }
    
    private function calculateRiskLevel($score) {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } elseif ($score >= 20) {
            return 'low';
        } else {
            return 'normal';
        }
    }
} 
