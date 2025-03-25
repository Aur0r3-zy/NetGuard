<?php

namespace Core\Monitor;

class TrafficMonitor {
    private $db;
    private $logger;
    private $algorithm;
    private $baseline;
    private $config;
    private $cache;
    private $thresholds;
    private $patterns;
    private $blacklist;
    private $whitelist;
    
    public function __construct($db, $logger, $algorithm, $cache = null) {
        $this->db = $db;
        $this->logger = $logger;
        $this->algorithm = $algorithm;
        $this->cache = $cache;
        $this->baseline = [];
        $this->config = $this->loadConfig();
        $this->thresholds = $this->config['thresholds'] ?? [];
        $this->patterns = $this->config['patterns'] ?? [];
        
        // 加载黑名单和白名单
        $this->loadLists();
    }
    
    private function loadConfig() {
        $configFile = __DIR__ . '/../../config/traffic.php';
        return file_exists($configFile) ? require $configFile : [];
    }
    
    private function loadLists() {
        try {
            // 加载黑名单
            $query = "SELECT * FROM blacklist WHERE status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $this->blacklist = $stmt->fetchAll();
            
            // 加载白名单
            $query = "SELECT * FROM whitelist WHERE status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $this->whitelist = $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('加载黑白名单失败：' . $e->getMessage());
        }
    }
    
    public function establishBaseline($duration = 3600) {
        try {
            // 收集历史流量数据
            $historicalData = $this->collectHistoricalData($duration);
            
            // 计算流量基线
            $this->baseline = $this->calculateBaseline($historicalData);
            
            // 保存基线数据
            $this->saveBaselineData();
            
            return [
                'status' => 'success',
                'message' => '流量基线建立成功',
                'baseline' => $this->baseline
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量基线建立失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量基线建立失败：' . $e->getMessage()
            ];
        }
    }
    
    private function saveBaselineData() {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO baseline_data (
                    packet_count, byte_count, protocols,
                    ports, ip_addresses, avg_packet_size,
                    packets_per_second, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->baseline['packet_count'],
                $this->baseline['byte_count'],
                json_encode($this->baseline['protocols']),
                json_encode($this->baseline['ports']),
                json_encode($this->baseline['ip_addresses']),
                $this->baseline['avg_packet_size'],
                $this->baseline['packets_per_second'],
                time()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('保存基线数据失败：' . $e->getMessage());
        }
    }
    
    public function monitorTraffic() {
        try {
            // 获取实时流量数据
            $currentTraffic = $this->getCurrentTraffic();
            
            // 检测异常流量
            $anomalies = $this->detectAnomalies($currentTraffic);
            
            // 生成告警
            $alerts = $this->generateAlerts($anomalies);
            
            // 生成统计指标
            $metrics = $this->generateMetrics($currentTraffic);
            
            // 分析流量模式
            $patterns = $this->analyzeTrafficPatterns($currentTraffic);
            
            // 评估风险
            $riskAssessment = $this->assessTrafficRisk($currentTraffic, $anomalies);
            
            // 生成建议
            $recommendations = $this->generateRecommendations($anomalies, $riskAssessment);
            
            // 保存监控数据
            $this->saveMonitoringData([
                'current_traffic' => $currentTraffic,
                'anomalies' => $anomalies,
                'alerts' => $alerts,
                'metrics' => $metrics,
                'patterns' => $patterns,
                'risk_assessment' => $riskAssessment,
                'recommendations' => $recommendations
            ]);
            
            return [
                'status' => 'success',
                'data' => [
                    'current_traffic' => $currentTraffic,
                    'anomalies' => $anomalies,
                    'alerts' => $alerts,
                    'metrics' => $metrics,
                    'patterns' => $patterns,
                    'risk_assessment' => $riskAssessment,
                    'recommendations' => $recommendations
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('流量监控失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '流量监控失败：' . $e->getMessage()
            ];
        }
    }
    
    private function collectHistoricalData($duration) {
        $data = [];
        
        try {
            // 从数据库获取历史数据
            $query = "SELECT * FROM traffic_data WHERE timestamp >= ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([time() - $duration]);
            
            while ($row = $stmt->fetch()) {
                $data[] = $row;
            }
            
            // 从缓存获取数据
            if ($this->cache) {
                $cachedData = $this->cache->get('historical_traffic_data');
                if ($cachedData) {
                    $data = array_merge($data, $cachedData);
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error('收集历史数据失败：' . $e->getMessage());
        }
        
        return $data;
    }
    
    private function calculateBaseline($historicalData) {
        $baseline = [
            'packet_count' => 0,
            'byte_count' => 0,
            'protocols' => [],
            'ports' => [],
            'ip_addresses' => [],
            'bandwidth' => [],
            'packet_rates' => [],
            'ip_ranges' => []
        ];
        
        try {
            foreach ($historicalData as $data) {
                // 计算数据包数量
                $baseline['packet_count']++;
                
                // 计算字节数
                $baseline['byte_count'] += $data['size'] ?? 0;
                
                // 统计协议分布
                $protocol = $data['protocol'] ?? 'unknown';
                $baseline['protocols'][$protocol] = ($baseline['protocols'][$protocol] ?? 0) + 1;
                
                // 统计端口分布
                $port = $data['port'] ?? 0;
                $baseline['ports'][$port] = ($baseline['ports'][$port] ?? 0) + 1;
                
                // 统计IP地址分布
                $ip = $data['source_ip'] ?? '';
                if ($ip) {
                    $baseline['ip_addresses'][$ip] = ($baseline['ip_addresses'][$ip] ?? 0) + 1;
                }
                
                // 计算带宽使用
                $timestamp = $data['timestamp'] ?? time();
                $hour = date('H', $timestamp);
                $baseline['bandwidth'][$hour] = ($baseline['bandwidth'][$hour] ?? 0) + ($data['size'] ?? 0);
                
                // 计算数据包速率
                $baseline['packet_rates'][$hour] = ($baseline['packet_rates'][$hour] ?? 0) + 1;
                
                // 统计IP范围
                if ($ip) {
                    $range = $this->getIPRange($ip);
                    $baseline['ip_ranges'][$range] = ($baseline['ip_ranges'][$range] ?? 0) + 1;
                }
            }
            
            // 计算平均值
            $count = count($historicalData);
            if ($count > 0) {
                $baseline['avg_packet_size'] = $baseline['byte_count'] / $count;
                $baseline['packets_per_second'] = $baseline['packet_count'] / ($duration ?? 3600);
                
                // 计算带宽平均值
                foreach ($baseline['bandwidth'] as $hour => $bytes) {
                    $baseline['bandwidth'][$hour] = $bytes / ($duration / 3600);
                }
                
                // 计算数据包速率平均值
                foreach ($baseline['packet_rates'] as $hour => $count) {
                    $baseline['packet_rates'][$hour] = $count / ($duration / 3600);
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error('计算流量基线失败：' . $e->getMessage());
        }
        
        return $baseline;
    }
    
    private function getCurrentTraffic() {
        $traffic = [
            'packet_count' => 0,
            'byte_count' => 0,
            'protocols' => [],
            'ports' => [],
            'ip_addresses' => [],
            'bandwidth' => [],
            'packet_rates' => [],
            'ip_ranges' => []
        ];
        
        try {
            // 获取最近一分钟的流量数据
            $query = "SELECT * FROM traffic_data WHERE timestamp >= ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([time() - 60]);
            
            while ($row = $stmt->fetch()) {
                // 更新统计数据
                $traffic['packet_count']++;
                $traffic['byte_count'] += $row['size'] ?? 0;
                
                // 更新协议统计
                $protocol = $row['protocol'] ?? 'unknown';
                $traffic['protocols'][$protocol] = ($traffic['protocols'][$protocol] ?? 0) + 1;
                
                // 更新端口统计
                $port = $row['port'] ?? 0;
                $traffic['ports'][$port] = ($traffic['ports'][$port] ?? 0) + 1;
                
                // 更新IP地址统计
                $ip = $row['source_ip'] ?? '';
                if ($ip) {
                    $traffic['ip_addresses'][$ip] = ($traffic['ip_addresses'][$ip] ?? 0) + 1;
                }
                
                // 更新带宽使用
                $hour = date('H', $row['timestamp']);
                $traffic['bandwidth'][$hour] = ($traffic['bandwidth'][$hour] ?? 0) + ($row['size'] ?? 0);
                
                // 更新数据包速率
                $traffic['packet_rates'][$hour] = ($traffic['packet_rates'][$hour] ?? 0) + 1;
                
                // 更新IP范围统计
                if ($ip) {
                    $range = $this->getIPRange($ip);
                    $traffic['ip_ranges'][$range] = ($traffic['ip_ranges'][$range] ?? 0) + 1;
                }
            }
            
            // 计算实时速率
            $traffic['current_bandwidth'] = $traffic['byte_count'] / 60; // 字节/秒
            $traffic['current_packet_rate'] = $traffic['packet_count'] / 60; // 包/秒
            
        } catch (\Exception $e) {
            $this->logger->error('获取当前流量数据失败：' . $e->getMessage());
        }
        
        return $traffic;
    }
    
    private function detectAnomalies($currentTraffic) {
        $anomalies = [];
        
        try {
            // 检查数据包数量异常
            if ($currentTraffic['packet_count'] > $this->baseline['packet_count'] * 2) {
                $anomalies[] = [
                    'type' => 'packet_count',
                    'severity' => 'high',
                    'description' => '数据包数量显著增加',
                    'details' => [
                        'current' => $currentTraffic['packet_count'],
                        'baseline' => $this->baseline['packet_count'],
                        'ratio' => $currentTraffic['packet_count'] / $this->baseline['packet_count']
                    ]
                ];
            }
            
            // 检查字节数异常
            if ($currentTraffic['byte_count'] > $this->baseline['byte_count'] * 2) {
                $anomalies[] = [
                    'type' => 'byte_count',
                    'severity' => 'high',
                    'description' => '流量字节数显著增加',
                    'details' => [
                        'current' => $currentTraffic['byte_count'],
                        'baseline' => $this->baseline['byte_count'],
                        'ratio' => $currentTraffic['byte_count'] / $this->baseline['byte_count']
                    ]
                ];
            }
            
            // 检查协议分布异常
            foreach ($currentTraffic['protocols'] as $protocol => $count) {
                $baselineCount = $this->baseline['protocols'][$protocol] ?? 0;
                if ($count > $baselineCount * 3) {
                    $anomalies[] = [
                        'type' => 'protocol_distribution',
                        'severity' => 'medium',
                        'description' => "协议 {$protocol} 的使用量异常增加",
                        'details' => [
                            'protocol' => $protocol,
                            'current' => $count,
                            'baseline' => $baselineCount,
                            'ratio' => $count / $baselineCount
                        ]
                    ];
                }
            }
            
            // 检查端口扫描
            $portScanThreshold = $this->thresholds['port_scan'] ?? 100;
            if (count($currentTraffic['ports']) > $portScanThreshold) {
                $anomalies[] = [
                    'type' => 'port_scan',
                    'severity' => 'high',
                    'description' => '检测到可能的端口扫描行为',
                    'details' => [
                        'unique_ports' => count($currentTraffic['ports']),
                        'threshold' => $portScanThreshold
                    ]
                ];
            }
            
            // 检查IP地址分布异常
            foreach ($currentTraffic['ip_ranges'] as $range => $count) {
                $baselineCount = $this->baseline['ip_ranges'][$range] ?? 0;
                if ($count > $baselineCount * 5) {
                    $anomalies[] = [
                        'type' => 'ip_distribution',
                        'severity' => 'medium',
                        'description' => "IP范围 {$range} 的流量异常增加",
                        'details' => [
                            'range' => $range,
                            'current' => $count,
                            'baseline' => $baselineCount,
                            'ratio' => $count / $baselineCount
                        ]
                    ];
                }
            }
            
            // 检查带宽使用异常
            foreach ($currentTraffic['bandwidth'] as $hour => $bytes) {
                $baselineBytes = $this->baseline['bandwidth'][$hour] ?? 0;
                if ($bytes > $baselineBytes * 2) {
                    $anomalies[] = [
                        'type' => 'bandwidth_usage',
                        'severity' => 'high',
                        'description' => "{$hour}时段的带宽使用异常增加",
                        'details' => [
                            'hour' => $hour,
                            'current' => $bytes,
                            'baseline' => $baselineBytes,
                            'ratio' => $bytes / $baselineBytes
                        ]
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error('检测流量异常失败：' . $e->getMessage());
        }
        
        return $anomalies;
    }
    
    private function generateAlerts($anomalies) {
        $alerts = [];
        
        try {
            foreach ($anomalies as $anomaly) {
                // 检查是否在黑名单中
                if ($this->isBlacklisted($anomaly)) {
                    $alerts[] = [
                        'level' => 'critical',
                        'message' => "检测到黑名单IP的异常流量：{$anomaly['description']}",
                        'timestamp' => time(),
                        'details' => $anomaly['details']
                    ];
                    continue;
                }
                
                // 检查是否在白名单中
                if ($this->isWhitelisted($anomaly)) {
                    continue;
                }
                
                // 根据严重程度生成告警
                if ($anomaly['severity'] === 'high') {
                    $alerts[] = [
                        'level' => 'critical',
                        'message' => $anomaly['description'],
                        'timestamp' => time(),
                        'details' => $anomaly['details']
                    ];
                } elseif ($anomaly['severity'] === 'medium') {
                    $alerts[] = [
                        'level' => 'warning',
                        'message' => $anomaly['description'],
                        'timestamp' => time(),
                        'details' => $anomaly['details']
                    ];
                }
            }
            
            // 保存告警记录
            $this->saveAlerts($alerts);
            
        } catch (\Exception $e) {
            $this->logger->error('生成告警失败：' . $e->getMessage());
        }
        
        return $alerts;
    }
    
    private function generateMetrics($currentTraffic) {
        $metrics = [
            'timestamp' => time(),
            'packet_count' => $currentTraffic['packet_count'] ?? 0,
            'byte_count' => $currentTraffic['byte_count'] ?? 0,
            'protocols' => $currentTraffic['protocols'] ?? [],
            'ports' => $currentTraffic['ports'] ?? [],
            'ip_addresses' => $currentTraffic['ip_addresses'] ?? [],
            'avg_packet_size' => $currentTraffic['byte_count'] / ($currentTraffic['packet_count'] ?? 1),
            'packets_per_second' => ($currentTraffic['packet_count'] ?? 0) / 60,
            'current_bandwidth' => $currentTraffic['current_bandwidth'] ?? 0,
            'current_packet_rate' => $currentTraffic['current_packet_rate'] ?? 0,
            'bandwidth_trend' => $this->calculateBandwidthTrend(),
            'packet_rate_trend' => $this->calculatePacketRateTrend(),
            'protocol_distribution' => $this->calculateProtocolDistribution($currentTraffic),
            'port_distribution' => $this->calculatePortDistribution($currentTraffic),
            'ip_distribution' => $this->calculateIPDistribution($currentTraffic)
        ];
        
        return $metrics;
    }
    
    private function analyzeTrafficPatterns($currentTraffic) {
        $patterns = [
            'normal_patterns' => [],
            'abnormal_patterns' => [],
            'trends' => [],
            'predictions' => []
        ];
        
        try {
            // 分析正常模式
            $patterns['normal_patterns'] = $this->identifyNormalPatterns($currentTraffic);
            
            // 分析异常模式
            $patterns['abnormal_patterns'] = $this->identifyAbnormalPatterns($currentTraffic);
            
            // 分析趋势
            $patterns['trends'] = $this->analyzeTrafficTrends($currentTraffic);
            
            // 预测未来趋势
            $patterns['predictions'] = $this->predictTrafficPatterns($currentTraffic);
            
        } catch (\Exception $e) {
            $this->logger->error('分析流量模式失败：' . $e->getMessage());
        }
        
        return $patterns;
    }
    
    private function assessTrafficRisk($currentTraffic, $anomalies) {
        $risk = [
            'level' => 'low',
            'score' => 0,
            'factors' => [],
            'recommendations' => []
        ];
        
        try {
            // 评估异常风险
            foreach ($anomalies as $anomaly) {
                $risk['score'] += $this->calculateAnomalyRiskScore($anomaly);
                $risk['factors'][] = [
                    'type' => 'anomaly',
                    'description' => $anomaly['description'],
                    'severity' => $anomaly['severity'],
                    'score' => $this->calculateAnomalyRiskScore($anomaly)
                ];
            }
            
            // 评估带宽风险
            $bandwidthRisk = $this->assessBandwidthRisk($currentTraffic);
            $risk['score'] += $bandwidthRisk['score'];
            $risk['factors'][] = $bandwidthRisk;
            
            // 评估协议风险
            $protocolRisk = $this->assessProtocolRisk($currentTraffic);
            $risk['score'] += $protocolRisk['score'];
            $risk['factors'][] = $protocolRisk;
            
            // 评估IP分布风险
            $ipRisk = $this->assessIPRisk($currentTraffic);
            $risk['score'] += $ipRisk['score'];
            $risk['factors'][] = $ipRisk;
            
            // 确定风险等级
            $risk['level'] = $this->determineRiskLevel($risk['score']);
            
            // 生成风险建议
            $risk['recommendations'] = $this->generateRiskRecommendations($risk);
            
        } catch (\Exception $e) {
            $this->logger->error('评估流量风险失败：' . $e->getMessage());
        }
        
        return $risk;
    }
    
    private function generateRecommendations($anomalies, $riskAssessment) {
        $recommendations = [];
        
        try {
            // 基于异常的建议
            foreach ($anomalies as $anomaly) {
                if ($anomaly['severity'] === 'high') {
                    $recommendations[] = [
                        'type' => 'anomaly_response',
                        'priority' => 'high',
                        'description' => "处理高危异常：{$anomaly['description']}",
                        'action' => $this->getAnomalyResponseAction($anomaly)
                    ];
                }
            }
            
            // 基于风险的建议
            if ($riskAssessment['level'] === 'high') {
                $recommendations[] = [
                    'type' => 'risk_mitigation',
                    'priority' => 'high',
                    'description' => '实施高风险缓解措施',
                    'action' => $this->getRiskMitigationAction($riskAssessment)
                ];
            }
            
            // 按优先级排序建议
            usort($recommendations, function($a, $b) {
                $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
                return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
            });
            
        } catch (\Exception $e) {
            $this->logger->error('生成建议失败：' . $e->getMessage());
        }
        
        return $recommendations;
    }
    
    private function saveMonitoringData($data) {
        try {
            // 保存到数据库
            $stmt = $this->db->prepare("
                INSERT INTO monitoring_data (
                    current_traffic, anomalies, alerts,
                    metrics, patterns, risk_assessment,
                    recommendations, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                json_encode($data['current_traffic']),
                json_encode($data['anomalies']),
                json_encode($data['alerts']),
                json_encode($data['metrics']),
                json_encode($data['patterns']),
                json_encode($data['risk_assessment']),
                json_encode($data['recommendations']),
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
    
    private function getIPRange($ip) {
        $parts = explode('.', $ip);
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
    }
    
    private function isBlacklisted($anomaly) {
        foreach ($this->blacklist as $item) {
            if (isset($anomaly['details']['ip']) && $this->isIPInRange($anomaly['details']['ip'], $item['ip_range'])) {
                return true;
            }
        }
        return false;
    }
    
    private function isWhitelisted($anomaly) {
        foreach ($this->whitelist as $item) {
            if (isset($anomaly['details']['ip']) && $this->isIPInRange($anomaly['details']['ip'], $item['ip_range'])) {
                return true;
            }
        }
        return false;
    }
    
    private function isIPInRange($ip, $range) {
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
    
    private function calculateAnomalyRiskScore($anomaly) {
        $severityScores = [
            'high' => 30,
            'medium' => 20,
            'low' => 10
        ];
        
        return $severityScores[$anomaly['severity']] ?? 0;
    }
    
    private function assessBandwidthRisk($currentTraffic) {
        $risk = [
            'type' => 'bandwidth',
            'description' => '带宽使用风险',
            'severity' => 'low',
            'score' => 0
        ];
        
        $currentBandwidth = $currentTraffic['current_bandwidth'] ?? 0;
        $baselineBandwidth = $this->baseline['avg_bandwidth'] ?? 0;
        
        if ($currentBandwidth > $baselineBandwidth * 3) {
            $risk['severity'] = 'high';
            $risk['score'] = 30;
        } elseif ($currentBandwidth > $baselineBandwidth * 2) {
            $risk['severity'] = 'medium';
            $risk['score'] = 20;
        }
        
        return $risk;
    }
    
    private function assessProtocolRisk($currentTraffic) {
        $risk = [
            'type' => 'protocol',
            'description' => '协议使用风险',
            'severity' => 'low',
            'score' => 0
        ];
        
        $suspiciousProtocols = ['ICMP', 'UDP', 'TCP'];
        $highRiskCount = 0;
        
        foreach ($currentTraffic['protocols'] as $protocol => $count) {
            if (in_array($protocol, $suspiciousProtocols) && $count > 1000) {
                $highRiskCount++;
            }
        }
        
        if ($highRiskCount >= 2) {
            $risk['severity'] = 'high';
            $risk['score'] = 30;
        } elseif ($highRiskCount >= 1) {
            $risk['severity'] = 'medium';
            $risk['score'] = 20;
        }
        
        return $risk;
    }
    
    private function assessIPRisk($currentTraffic) {
        $risk = [
            'type' => 'ip',
            'description' => 'IP分布风险',
            'severity' => 'low',
            'score' => 0
        ];
        
        $uniqueIPs = count($currentTraffic['ip_addresses']);
        $baselineIPs = count($this->baseline['ip_addresses']);
        
        if ($uniqueIPs > $baselineIPs * 3) {
            $risk['severity'] = 'high';
            $risk['score'] = 30;
        } elseif ($uniqueIPs > $baselineIPs * 2) {
            $risk['severity'] = 'medium';
            $risk['score'] = 20;
        }
        
        return $risk;
    }
    
    private function determineRiskLevel($score) {
        if ($score >= 80) {
            return 'high';
        } elseif ($score >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private function generateRiskRecommendations($risk) {
        $recommendations = [];
        
        if ($risk['level'] === 'high') {
            $recommendations[] = [
                'type' => 'immediate_action',
                'priority' => 'high',
                'description' => '立即采取行动处理高风险情况',
                'steps' => [
                    '检查异常流量来源',
                    '实施流量限制',
                    '加强监控'
                ]
            ];
        }
        
        foreach ($risk['factors'] as $factor) {
            if ($factor['severity'] === 'high') {
                $recommendations[] = [
                    'type' => 'risk_factor',
                    'priority' => 'high',
                    'description' => "处理高风险因素：{$factor['description']}",
                    'steps' => $this->getRiskFactorSteps($factor)
                ];
            }
        }
        
        return $recommendations;
    }
    
    private function getRiskFactorSteps($factor) {
        $steps = [];
        
        switch ($factor['type']) {
            case 'bandwidth':
                $steps = [
                    '分析带宽使用情况',
                    '实施带宽限制',
                    '优化网络配置'
                ];
                break;
            case 'protocol':
                $steps = [
                    '检查协议使用情况',
                    '限制可疑协议',
                    '更新防火墙规则'
                ];
                break;
            case 'ip':
                $steps = [
                    '分析IP分布情况',
                    '实施IP限制',
                    '加强访问控制'
                ];
                break;
        }
        
        return $steps;
    }
    
    private function getAnomalyResponseAction($anomaly) {
        $action = [
            'type' => 'anomaly_response',
            'steps' => []
        ];
        
        switch ($anomaly['type']) {
            case 'packet_count':
                $action['steps'] = [
                    '检查数据包来源',
                    '实施流量限制',
                    '更新防火墙规则'
                ];
                break;
            case 'byte_count':
                $action['steps'] = [
                    '分析流量来源',
                    '实施带宽限制',
                    '优化网络配置'
                ];
                break;
            case 'protocol_distribution':
                $action['steps'] = [
                    '检查协议使用情况',
                    '限制可疑协议',
                    '更新安全策略'
                ];
                break;
            case 'port_scan':
                $action['steps'] = [
                    '识别扫描来源',
                    '阻止可疑IP',
                    '加强端口监控'
                ];
                break;
            case 'ip_distribution':
                $action['steps'] = [
                    '分析IP分布情况',
                    '实施IP限制',
                    '更新访问控制'
                ];
                break;
            case 'bandwidth_usage':
                $action['steps'] = [
                    '检查带宽使用情况',
                    '实施带宽限制',
                    '优化网络配置'
                ];
                break;
        }
        
        return $action;
    }
    
    private function getRiskMitigationAction($riskAssessment) {
        return [
            'type' => 'risk_mitigation',
            'steps' => [
                '分析风险因素',
                '制定缓解计划',
                '实施控制措施',
                '监控效果'
            ]
        ];
    }
    
    private function validateTrafficData($data) {
        // 流量数据验证
        $requiredFields = ['packet_count', 'byte_count', 'protocols', 'ports'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // 验证数值类型
        if (!is_numeric($data['packet_count']) || $data['packet_count'] < 0) {
            return false;
        }
        
        if (!is_numeric($data['byte_count']) || $data['byte_count'] < 0) {
            return false;
        }
        
        // 验证协议和端口数据
        if (!is_array($data['protocols']) || !is_array($data['ports'])) {
            return false;
        }
        
        return true;
    }
    
    private function sanitizeTrafficData($data) {
        // 流量数据清理
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeTrafficData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    private function validateBaseline($baseline) {
        // 基线数据验证
        $requiredFields = [
            'packet_count', 'byte_count', 'protocols',
            'ports', 'ip_addresses', 'avg_packet_size'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($baseline[$field])) {
                throw new \Exception("基线数据缺少必要字段: {$field}");
            }
        }
        
        // 验证数值类型
        if (!is_numeric($baseline['packet_count']) || $baseline['packet_count'] < 0) {
            throw new \Exception('无效的数据包数量');
        }
        
        if (!is_numeric($baseline['byte_count']) || $baseline['byte_count'] < 0) {
            throw new \Exception('无效的字节数');
        }
        
        if (!is_numeric($baseline['avg_packet_size']) || $baseline['avg_packet_size'] < 0) {
            throw new \Exception('无效的平均数据包大小');
        }
        
        return true;
    }
    
    private function logTrafficAnomaly($anomaly) {
        // 记录流量异常
        try {
            $stmt = $this->db->prepare("
                INSERT INTO traffic_anomalies (
                    type, severity, description, timestamp,
                    details, source_ip, action_taken
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $anomaly['type'],
                $anomaly['severity'],
                $anomaly['description'],
                time(),
                json_encode($this->sanitizeTrafficData($anomaly['details'])),
                $anomaly['details']['ip'] ?? 'unknown',
                json_encode($this->determineAnomalyAction($anomaly))
            ]);
        } catch (\Exception $e) {
            $this->logger->error('记录流量异常失败：' . $e->getMessage());
        }
    }
    
    private function determineAnomalyAction($anomaly) {
        // 根据异常类型确定采取的行动
        switch ($anomaly['type']) {
            case 'packet_count':
            case 'byte_count':
                return [
                    'action' => 'rate_limit',
                    'duration' => 300,
                    'threshold' => $anomaly['details']['current'] * 0.5
                ];
            case 'protocol_distribution':
                return [
                    'action' => 'protocol_filter',
                    'protocol' => $anomaly['details']['protocol'],
                    'duration' => 1800
                ];
            case 'port_scan':
                return [
                    'action' => 'block_ip',
                    'duration' => 3600,
                    'reason' => '端口扫描行为'
                ];
            default:
                return [
                    'action' => 'monitor',
                    'duration' => 900,
                    'reason' => '异常流量模式'
                ];
        }
    }
    
    private function updateTrafficStats($stats) {
        // 更新流量统计
        try {
            $stmt = $this->db->prepare("
                INSERT INTO traffic_stats (
                    packet_count, byte_count, protocols,
                    ports, ip_addresses, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $stats['packet_count'],
                $stats['byte_count'],
                json_encode($stats['protocols']),
                json_encode($stats['ports']),
                json_encode($stats['ip_addresses']),
                time()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('更新流量统计失败：' . $e->getMessage());
        }
    }
    
    private function validateThresholds($thresholds) {
        // 阈值验证
        $requiredFields = [
            'max_packet_size', 'max_bandwidth',
            'max_packet_rate', 'port_scan'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($thresholds[$field])) {
                throw new \Exception("缺少必要的阈值配置: {$field}");
            }
        }
        
        // 验证数值类型和范围
        if (!is_numeric($thresholds['max_packet_size']) || $thresholds['max_packet_size'] <= 0) {
            throw new \Exception('无效的最大数据包大小阈值');
        }
        
        if (!is_numeric($thresholds['max_bandwidth']) || $thresholds['max_bandwidth'] <= 0) {
            throw new \Exception('无效的最大带宽阈值');
        }
        
        if (!is_numeric($thresholds['max_packet_rate']) || $thresholds['max_packet_rate'] <= 0) {
            throw new \Exception('无效的最大数据包速率阈值');
        }
        
        if (!is_numeric($thresholds['port_scan']) || $thresholds['port_scan'] <= 0) {
            throw new \Exception('无效的端口扫描阈值');
        }
        
        return true;
    }
} 