<?php

namespace Core\Monitor;

class SecurityMonitor {
    private $db;
    private $logger;
    private $algorithm;
    private $config;
    private $cache;
    private $eventTypes;
    private $policyRules;
    private $vulnerabilityScanner;
    private $threatIntelligence;
    
    public function __construct($db, $logger, $algorithm, $cache = null) {
        $this->db = $db;
        $this->logger = $logger;
        $this->algorithm = $algorithm;
        $this->cache = $cache;
        $this->config = $this->loadConfig();
        $this->eventTypes = $this->config['event_types'] ?? [];
        $this->policyRules = $this->config['policy_rules'] ?? [];
        
        // 初始化漏洞扫描器和威胁情报
        $this->initializeComponents();
    }
    
    private function loadConfig() {
        $configFile = __DIR__ . '/../../config/security.php';
        return file_exists($configFile) ? require $configFile : [];
    }
    
    private function initializeComponents() {
        try {
            // 初始化漏洞扫描器
            $this->vulnerabilityScanner = new VulnerabilityScanner($this->db, $this->logger);
            
            // 初始化威胁情报
            $this->threatIntelligence = new ThreatIntelligence($this->db, $this->logger);
        } catch (\Exception $e) {
            $this->logger->error('初始化组件失败：' . $e->getMessage());
        }
    }
    
    public function monitorSecurity() {
        try {
            // 追踪安全事件
            $events = $this->trackSecurityEvents();
            
            // 审核安全策略
            $policyAudit = $this->auditSecurityPolicies();
            
            // 分析安全态势
            $situation = $this->analyzeSecuritySituation();
            
            // 生成响应建议
            $recommendations = $this->generateResponseRecommendations($events, $policyAudit, $situation);
            
            // 保存监控数据
            $this->saveMonitoringData([
                'events' => $events,
                'policy_audit' => $policyAudit,
                'situation' => $situation,
                'recommendations' => $recommendations
            ]);
            
            return [
                'status' => 'success',
                'data' => [
                    'events' => $events,
                    'policy_audit' => $policyAudit,
                    'situation' => $situation,
                    'recommendations' => $recommendations
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('安全监控失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '安全监控失败：' . $e->getMessage()
            ];
        }
    }
    
    private function trackSecurityEvents() {
        $events = [];
        
        try {
            // 获取最近的安全事件
            $query = "SELECT * FROM security_events WHERE timestamp >= ? ORDER BY timestamp DESC LIMIT 100";
            $stmt = $this->db->prepare($query);
            $stmt->execute([time() - 86400]); // 最近24小时
            
            while ($row = $stmt->fetch()) {
                $event = [
                    'id' => $row['id'],
                    'type' => $row['event_type'],
                    'severity' => $row['severity'],
                    'description' => $row['description'],
                    'timestamp' => $row['timestamp'],
                    'source' => $row['source'],
                    'status' => $row['status'],
                    'details' => json_decode($row['details'], true),
                    'affected_assets' => json_decode($row['affected_assets'], true),
                    'mitigation_steps' => json_decode($row['mitigation_steps'], true),
                    'related_events' => $this->getRelatedEvents($row['id'])
                ];
                
                // 添加事件分析
                $event['analysis'] = $this->analyzeEvent($event);
                
                $events[] = $event;
            }
            
            // 更新事件统计
            $this->updateEventStatistics($events);
            
            return $events;
        } catch (\Exception $e) {
            $this->logger->error('追踪安全事件失败：' . $e->getMessage());
            return [];
        }
    }
    
    private function getRelatedEvents($eventId) {
        try {
            $query = "SELECT * FROM security_events WHERE related_event_id = ? ORDER BY timestamp DESC LIMIT 5";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$eventId]);
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取相关事件失败：' . $e->getMessage());
            return [];
        }
    }
    
    private function analyzeEvent($event) {
        $analysis = [
            'risk_level' => $this->calculateEventRiskLevel($event),
            'impact_scope' => $this->assessEventImpact($event),
            'correlation' => $this->findEventCorrelations($event),
            'trend' => $this->analyzeEventTrend($event),
            'recommendations' => $this->generateEventRecommendations($event)
        ];
        
        return $analysis;
    }
    
    private function calculateEventRiskLevel($event) {
        $riskScore = 0;
        
        // 基于严重程度
        $severityScores = [
            'critical' => 40,
            'high' => 30,
            'medium' => 20,
            'low' => 10
        ];
        $riskScore += $severityScores[$event['severity']] ?? 0;
        
        // 基于影响范围
        $affectedAssets = count($event['affected_assets'] ?? []);
        $riskScore += min($affectedAssets * 5, 30);
        
        // 基于事件类型
        $eventTypeRisk = $this->eventTypes[$event['type']]['risk_score'] ?? 0;
        $riskScore += $eventTypeRisk;
        
        // 确定风险等级
        if ($riskScore >= 80) {
            return 'critical';
        } elseif ($riskScore >= 60) {
            return 'high';
        } elseif ($riskScore >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private function assessEventImpact($event) {
        $impact = [
            'scope' => 'local',
            'affected_systems' => [],
            'business_impact' => 'low',
            'data_impact' => 'none',
            'recovery_time' => 'short'
        ];
        
        // 评估影响范围
        if (isset($event['affected_assets'])) {
            $impact['affected_systems'] = $event['affected_assets'];
            $impact['scope'] = count($event['affected_assets']) > 5 ? 'wide' : 'local';
        }
        
        // 评估业务影响
        if (isset($event['details']['business_impact'])) {
            $impact['business_impact'] = $event['details']['business_impact'];
        }
        
        // 评估数据影响
        if (isset($event['details']['data_impact'])) {
            $impact['data_impact'] = $event['details']['data_impact'];
        }
        
        // 评估恢复时间
        if (isset($event['details']['recovery_time'])) {
            $impact['recovery_time'] = $event['details']['recovery_time'];
        }
        
        return $impact;
    }
    
    private function findEventCorrelations($event) {
        $correlations = [
            'related_events' => [],
            'common_patterns' => [],
            'attack_chain' => []
        ];
        
        try {
            // 查找相关事件
            $query = "SELECT * FROM security_events 
                     WHERE source = ? AND timestamp BETWEEN ? AND ? 
                     ORDER BY timestamp DESC LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $event['source'],
                $event['timestamp'] - 3600,
                $event['timestamp'] + 3600
            ]);
            
            $correlations['related_events'] = $stmt->fetchAll();
            
            // 分析共同模式
            $correlations['common_patterns'] = $this->analyzeCommonPatterns($correlations['related_events']);
            
            // 构建攻击链
            $correlations['attack_chain'] = $this->buildAttackChain($correlations['related_events']);
            
        } catch (\Exception $e) {
            $this->logger->error('查找事件关联失败：' . $e->getMessage());
        }
        
        return $correlations;
    }
    
    private function analyzeEventTrend($event) {
        $trend = [
            'frequency' => 'normal',
            'pattern' => 'random',
            'prediction' => 'stable',
            'historical_data' => []
        ];
        
        try {
            // 获取历史数据
            $query = "SELECT * FROM security_events 
                     WHERE type = ? AND timestamp >= ? 
                     ORDER BY timestamp ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $event['type'],
                time() - 604800 // 最近7天
            ]);
            
            $historicalData = $stmt->fetchAll();
            
            // 分析频率
            $trend['frequency'] = $this->analyzeEventFrequency($historicalData);
            
            // 分析模式
            $trend['pattern'] = $this->analyzeEventPattern($historicalData);
            
            // 预测趋势
            $trend['prediction'] = $this->predictEventTrend($historicalData);
            
            $trend['historical_data'] = $historicalData;
            
        } catch (\Exception $e) {
            $this->logger->error('分析事件趋势失败：' . $e->getMessage());
        }
        
        return $trend;
    }
    
    private function generateEventRecommendations($event) {
        $recommendations = [];
        
        // 基于事件类型的建议
        if (isset($this->eventTypes[$event['type']]['recommendations'])) {
            $recommendations = array_merge(
                $recommendations,
                $this->eventTypes[$event['type']]['recommendations']
            );
        }
        
        // 基于严重程度的建议
        if ($event['severity'] === 'critical' || $event['severity'] === 'high') {
            $recommendations[] = [
                'type' => 'immediate_action',
                'priority' => 'high',
                'description' => '立即采取行动处理高危事件',
                'steps' => [
                    '隔离受影响系统',
                    '启动事件响应流程',
                    '通知相关团队'
                ]
            ];
        }
        
        // 基于影响范围的建议
        if (count($event['affected_assets'] ?? []) > 5) {
            $recommendations[] = [
                'type' => 'containment',
                'priority' => 'medium',
                'description' => '实施遏制措施防止事件扩散',
                'steps' => [
                    '评估影响范围',
                    '实施网络隔离',
                    '加强监控'
                ]
            ];
        }
        
        return $recommendations;
    }
    
    private function updateEventStatistics($events) {
        try {
            $stats = [
                'total_events' => count($events),
                'by_type' => [],
                'by_severity' => [],
                'by_source' => [],
                'trends' => []
            ];
            
            foreach ($events as $event) {
                // 按类型统计
                $stats['by_type'][$event['type']] = ($stats['by_type'][$event['type']] ?? 0) + 1;
                
                // 按严重程度统计
                $stats['by_severity'][$event['severity']] = ($stats['by_severity'][$event['severity']] ?? 0) + 1;
                
                // 按来源统计
                $stats['by_source'][$event['source']] = ($stats['by_source'][$event['source']] ?? 0) + 1;
            }
            
            // 计算趋势
            $stats['trends'] = $this->calculateEventTrends($events);
            
            // 保存统计信息
            $this->saveEventStatistics($stats);
            
        } catch (\Exception $e) {
            $this->logger->error('更新事件统计失败：' . $e->getMessage());
        }
    }
    
    private function auditSecurityPolicies() {
        $audit = [
            'policies' => [],
            'violations' => [],
            'compliance_score' => 0,
            'risk_assessment' => [],
            'recommendations' => []
        ];
        
        try {
            // 获取所有安全策略
            $query = "SELECT * FROM security_policies";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $policy = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'status' => $row['status'],
                    'last_updated' => $row['last_updated'],
                    'rules' => json_decode($row['rules'], true),
                    'scope' => json_decode($row['scope'], true)
                ];
                
                // 检查策略合规性
                $violations = $this->checkPolicyCompliance($policy);
                if (!empty($violations)) {
                    $audit['violations'][] = [
                        'policy_id' => $policy['id'],
                        'violations' => $violations
                    ];
                }
                
                // 评估策略风险
                $riskAssessment = $this->assessPolicyRisk($policy);
                $policy['risk_assessment'] = $riskAssessment;
                
                $audit['policies'][] = $policy;
            }
            
            // 计算合规性得分
            $totalPolicies = count($audit['policies']);
            $violatingPolicies = count($audit['violations']);
            $audit['compliance_score'] = $totalPolicies > 0 
                ? round(($totalPolicies - $violatingPolicies) / $totalPolicies * 100, 2)
                : 0;
            
            // 生成风险评估
            $audit['risk_assessment'] = $this->generatePolicyRiskAssessment($audit['policies']);
            
            // 生成建议
            $audit['recommendations'] = $this->generatePolicyRecommendations($audit);
            
        } catch (\Exception $e) {
            $this->logger->error('审核安全策略失败：' . $e->getMessage());
        }
        
        return $audit;
    }
    
    private function checkPolicyCompliance($policy) {
        $violations = [];
        
        try {
            foreach ($policy['rules'] as $rule) {
                $compliance = $this->checkRuleCompliance($rule);
                if (!$compliance['compliant']) {
                    $violations[] = [
                        'rule_id' => $rule['id'],
                        'description' => $rule['description'],
                        'violation_details' => $compliance['details'],
                        'severity' => $rule['severity']
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('检查策略合规性失败：' . $e->getMessage());
        }
        
        return $violations;
    }
    
    private function checkRuleCompliance($rule) {
        $compliance = [
            'compliant' => true,
            'details' => []
        ];
        
        try {
            switch ($rule['type']) {
                case 'access_control':
                    $compliance = $this->checkAccessControlCompliance($rule);
                    break;
                case 'password_policy':
                    $compliance = $this->checkPasswordPolicyCompliance($rule);
                    break;
                case 'network_security':
                    $compliance = $this->checkNetworkSecurityCompliance($rule);
                    break;
                case 'data_protection':
                    $compliance = $this->checkDataProtectionCompliance($rule);
                    break;
                default:
                    $compliance['compliant'] = false;
                    $compliance['details'][] = '未知的规则类型';
            }
        } catch (\Exception $e) {
            $this->logger->error('检查规则合规性失败：' . $e->getMessage());
            $compliance['compliant'] = false;
            $compliance['details'][] = '检查过程发生错误';
        }
        
        return $compliance;
    }
    
    private function analyzeSecuritySituation() {
        $situation = [
            'risk_level' => 'low',
            'threats' => [],
            'vulnerabilities' => [],
            'trends' => [],
            'assets' => [],
            'compliance' => [],
            'recommendations' => []
        ];
        
        try {
            // 分析当前威胁
            $situation['threats'] = $this->analyzeCurrentThreats();
            
            // 分析漏洞
            $situation['vulnerabilities'] = $this->analyzeVulnerabilities();
            
            // 分析安全趋势
            $situation['trends'] = $this->analyzeSecurityTrends();
            
            // 分析资产安全状态
            $situation['assets'] = $this->analyzeAssetSecurity();
            
            // 分析合规性
            $situation['compliance'] = $this->analyzeCompliance();
            
            // 确定整体风险等级
            $situation['risk_level'] = $this->determineRiskLevel(
                $situation['threats'],
                $situation['vulnerabilities'],
                $situation['compliance']
            );
            
            // 生成建议
            $situation['recommendations'] = $this->generateSituationRecommendations($situation);
            
        } catch (\Exception $e) {
            $this->logger->error('分析安全态势失败：' . $e->getMessage());
        }
        
        return $situation;
    }
    
    private function analyzeCurrentThreats() {
        $threats = [];
        
        try {
            // 获取当前活跃威胁
            $query = "SELECT * FROM threats WHERE status = 'active' ORDER BY severity DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $threat = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'severity' => $row['severity'],
                    'description' => $row['description'],
                    'source' => $row['source'],
                    'targets' => json_decode($row['targets'], true),
                    'indicators' => json_decode($row['indicators'], true),
                    'mitigation' => json_decode($row['mitigation'], true)
                ];
                
                // 添加威胁分析
                $threat['analysis'] = $this->analyzeThreat($threat);
                
                $threats[] = $threat;
            }
            
            // 更新威胁情报
            $this->updateThreatIntelligence($threats);
            
        } catch (\Exception $e) {
            $this->logger->error('分析当前威胁失败：' . $e->getMessage());
        }
        
        return $threats;
    }
    
    private function analyzeVulnerabilities() {
        $vulnerabilities = [];
        
        try {
            // 获取最新漏洞扫描结果
            $scanResults = $this->vulnerabilityScanner->getLatestScanResults();
            
            foreach ($scanResults as $result) {
                $vulnerability = [
                    'id' => $result['id'],
                    'type' => $result['type'],
                    'severity' => $result['severity'],
                    'description' => $result['description'],
                    'affected_systems' => $result['affected_systems'],
                    'cve_reference' => $result['cve_reference'],
                    'patch_status' => $result['patch_status'],
                    'exploit_available' => $result['exploit_available']
                ];
                
                // 添加漏洞分析
                $vulnerability['analysis'] = $this->analyzeVulnerability($vulnerability);
                
                $vulnerabilities[] = $vulnerability;
            }
            
            // 更新漏洞数据库
            $this->updateVulnerabilityDatabase($vulnerabilities);
            
        } catch (\Exception $e) {
            $this->logger->error('分析漏洞失败：' . $e->getMessage());
        }
        
        return $vulnerabilities;
    }
    
    private function analyzeSecurityTrends() {
        $trends = [
            'event_trends' => [],
            'threat_trends' => [],
            'vulnerability_trends' => [],
            'risk_trends' => []
        ];
        
        try {
            // 分析事件趋势
            $trends['event_trends'] = $this->analyzeEventTrends();
            
            // 分析威胁趋势
            $trends['threat_trends'] = $this->analyzeThreatTrends();
            
            // 分析漏洞趋势
            $trends['vulnerability_trends'] = $this->analyzeVulnerabilityTrends();
            
            // 分析风险趋势
            $trends['risk_trends'] = $this->analyzeRiskTrends();
            
        } catch (\Exception $e) {
            $this->logger->error('分析安全趋势失败：' . $e->getMessage());
        }
        
        return $trends;
    }
    
    private function analyzeAssetSecurity() {
        $assets = [];
        
        try {
            // 获取所有资产
            $query = "SELECT * FROM assets WHERE status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $asset = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'security_level' => $row['security_level'],
                    'vulnerabilities' => json_decode($row['vulnerabilities'], true),
                    'threats' => json_decode($row['threats'], true),
                    'compliance' => json_decode($row['compliance'], true)
                ];
                
                // 添加资产安全分析
                $asset['security_analysis'] = $this->analyzeAssetSecurityStatus($asset);
                
                $assets[] = $asset;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('分析资产安全失败：' . $e->getMessage());
        }
        
        return $assets;
    }
    
    private function analyzeCompliance() {
        $compliance = [
            'overall_score' => 0,
            'standards' => [],
            'violations' => [],
            'trends' => []
        ];
        
        try {
            // 获取合规性标准
            $query = "SELECT * FROM compliance_standards";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $standard = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'version' => $row['version'],
                    'requirements' => json_decode($row['requirements'], true),
                    'compliance_score' => 0,
                    'violations' => []
                ];
                
                // 检查合规性
                $complianceCheck = $this->checkStandardCompliance($standard);
                $standard['compliance_score'] = $complianceCheck['score'];
                $standard['violations'] = $complianceCheck['violations'];
                
                $compliance['standards'][] = $standard;
                
                // 更新违规记录
                if (!empty($complianceCheck['violations'])) {
                    $compliance['violations'][] = [
                        'standard_id' => $standard['id'],
                        'violations' => $complianceCheck['violations']
                    ];
                }
            }
            
            // 计算总体得分
            $totalScore = 0;
            $totalStandards = count($compliance['standards']);
            foreach ($compliance['standards'] as $standard) {
                $totalScore += $standard['compliance_score'];
            }
            $compliance['overall_score'] = $totalStandards > 0 
                ? round($totalScore / $totalStandards, 2)
                : 0;
            
            // 分析合规性趋势
            $compliance['trends'] = $this->analyzeComplianceTrends();
            
        } catch (\Exception $e) {
            $this->logger->error('分析合规性失败：' . $e->getMessage());
        }
        
        return $compliance;
    }
    
    private function generateResponseRecommendations($events, $policyAudit, $situation) {
        $recommendations = [];
        
        try {
            // 基于安全事件的建议
            foreach ($events as $event) {
                if ($event['severity'] === 'high') {
                    $recommendations[] = [
                        'type' => 'event_response',
                        'priority' => 'high',
                        'description' => "立即处理高危安全事件：{$event['description']}",
                        'action' => $this->getEventResponseAction($event)
                    ];
                }
            }
            
            // 基于策略审核的建议
            foreach ($policyAudit['violations'] as $violation) {
                $recommendations[] = [
                    'type' => 'policy_update',
                    'priority' => 'medium',
                    'description' => "更新安全策略以解决违规问题",
                    'action' => $this->getPolicyUpdateAction($violation)
                ];
            }
            
            // 基于安全态势的建议
            if ($situation['risk_level'] === 'high') {
                $recommendations[] = [
                    'type' => 'risk_mitigation',
                    'priority' => 'high',
                    'description' => '实施高风险缓解措施',
                    'action' => $this->getRiskMitigationAction($situation)
                ];
            }
            
            // 基于合规性的建议
            foreach ($situation['compliance']['violations'] as $violation) {
                $recommendations[] = [
                    'type' => 'compliance_remediation',
                    'priority' => 'medium',
                    'description' => "解决合规性违规问题",
                    'action' => $this->getComplianceRemediationAction($violation)
                ];
            }
            
            // 基于资产安全的建议
            foreach ($situation['assets'] as $asset) {
                if ($asset['security_analysis']['risk_level'] === 'high') {
                    $recommendations[] = [
                        'type' => 'asset_security',
                        'priority' => 'high',
                        'description' => "加强资产安全防护：{$asset['name']}",
                        'action' => $this->getAssetSecurityAction($asset)
                    ];
                }
            }
            
            // 按优先级排序建议
            usort($recommendations, function($a, $b) {
                $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
                return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
            });
            
        } catch (\Exception $e) {
            $this->logger->error('生成响应建议失败：' . $e->getMessage());
        }
        
        return $recommendations;
    }
    
    private function saveMonitoringData($data) {
        try {
            // 保存到数据库
            $stmt = $this->db->prepare("
                INSERT INTO monitoring_data (
                    events, policy_audit, situation,
                    recommendations, timestamp
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                json_encode($data['events']),
                json_encode($data['policy_audit']),
                json_encode($data['situation']),
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
    
    private function validateEvent($event) {
        // 事件数据验证
        $requiredFields = ['type', 'severity', 'description', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }
        
        // 验证严重程度
        if (!in_array($event['severity'], ['critical', 'high', 'medium', 'low'])) {
            return false;
        }
        
        // 验证时间戳
        if (!is_numeric($event['timestamp']) || $event['timestamp'] > time()) {
            return false;
        }
        
        return true;
    }
    
    private function sanitizeEventData($event) {
        // 事件数据清理
        $sanitized = [];
        
        foreach ($event as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeEventData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    private function validatePolicy($policy) {
        // 策略验证
        $requiredFields = ['id', 'name', 'description', 'status', 'rules'];
        foreach ($requiredFields as $field) {
            if (!isset($policy[$field])) {
                throw new \Exception("策略缺少必要字段: {$field}");
            }
        }
        
        // 验证规则
        if (!is_array($policy['rules'])) {
            throw new \Exception('策略规则必须是数组');
        }
        
        foreach ($policy['rules'] as $rule) {
            $this->validateRule($rule);
        }
        
        return true;
    }
    
    private function validateRule($rule) {
        // 规则验证
        $requiredFields = ['id', 'type', 'description', 'severity'];
        foreach ($requiredFields as $field) {
            if (!isset($rule[$field])) {
                throw new \Exception("规则缺少必要字段: {$field}");
            }
        }
        
        // 验证规则类型
        $validTypes = ['access_control', 'password_policy', 'network_security', 'data_protection'];
        if (!in_array($rule['type'], $validTypes)) {
            throw new \Exception("无效的规则类型: {$rule['type']}");
        }
        
        // 验证严重程度
        if (!in_array($rule['severity'], ['high', 'medium', 'low'])) {
            throw new \Exception("无效的严重程度: {$rule['severity']}");
        }
        
        return true;
    }
    
    private function logPolicyViolation($violation) {
        // 记录策略违规
        try {
            $stmt = $this->db->prepare("
                INSERT INTO policy_violations (
                    policy_id, rule_id, violation_type, severity,
                    description, timestamp, details
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $violation['policy_id'],
                $violation['rule_id'],
                $violation['type'],
                $violation['severity'],
                $violation['description'],
                time(),
                json_encode($this->sanitizeEventData($violation['details']))
            ]);
        } catch (\Exception $e) {
            $this->logger->error('记录策略违规失败：' . $e->getMessage());
        }
    }
    
    private function updatePolicyStatus($policyId, $status) {
        // 更新策略状态
        try {
            $stmt = $this->db->prepare("
                UPDATE security_policies 
                SET status = ?, last_updated = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$status, time(), $policyId]);
        } catch (\Exception $e) {
            $this->logger->error('更新策略状态失败：' . $e->getMessage());
        }
    }
    
    private function validateComplianceData($data) {
        // 合规性数据验证
        if (!isset($data['standard_id']) || !is_numeric($data['standard_id'])) {
            return false;
        }
        
        if (!isset($data['compliance_score']) || !is_numeric($data['compliance_score'])) {
            return false;
        }
        
        if ($data['compliance_score'] < 0 || $data['compliance_score'] > 100) {
            return false;
        }
        
        return true;
    }
    
    private function logComplianceCheck($check) {
        // 记录合规性检查
        try {
            $stmt = $this->db->prepare("
                INSERT INTO compliance_checks (
                    standard_id, compliance_score, violations,
                    timestamp, details
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $check['standard_id'],
                $check['compliance_score'],
                json_encode($this->sanitizeEventData($check['violations'])),
                time(),
                json_encode($this->sanitizeEventData($check['details']))
            ]);
        } catch (\Exception $e) {
            $this->logger->error('记录合规性检查失败：' . $e->getMessage());
        }
    }
} 