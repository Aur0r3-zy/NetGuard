<?php

namespace App\Core\Data;

use App\Core\Logger\Logger;

class RiskAssessment {
    private $db;
    private $logger;
    
    public function __construct(Logger $logger) {
        $this->db = new \PDO(
            "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
            env('DB_USERNAME'),
            env('DB_PASSWORD')
        );
        $this->logger = $logger;
    }
    
    public function getOverallRisk(): float {
        try {
            $stmt = $this->db->prepare("
                SELECT AVG(risk_score) as overall_risk
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            return (float)$stmt->fetch()['overall_risk'];
        } catch (\Exception $e) {
            $this->logger->error('获取总体风险评分失败：' . $e->getMessage());
            return 0.0;
        }
    }
    
    public function getRiskComponents(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    network_risk,
                    system_risk,
                    application_risk,
                    data_risk,
                    user_risk
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取风险组件失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getRiskTrend(string $period = 'day'): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(risk_score) as risk_score
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 " . strtoupper($period) . ")
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('获取风险趋势失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getRiskFactors(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    vulnerabilities,
                    threats,
                    impacts,
                    controls
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取风险因素失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getVulnerabilities(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low
                FROM vulnerabilities
                WHERE status = 'active'
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取漏洞统计失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getThreats(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(DISTINCT category) as categories,
                    COUNT(DISTINCT source) as sources
                FROM threats
                WHERE status = 'active'
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取威胁统计失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getImpacts(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    business_impact,
                    financial_impact,
                    operational_impact,
                    reputation_impact
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取影响分析失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getControls(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    preventive_controls,
                    detective_controls,
                    corrective_controls,
                    control_effectiveness
                FROM risk_assessments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            $this->logger->error('获取控制措施失败：' . $e->getMessage());
            return [];
        }
    }
    
    public function getMitigationRecommendations(): array {
        try {
            $riskLevel = $this->getOverallRisk();
            $recommendations = [];
            
            if ($riskLevel >= 0.8) {
                $recommendations['immediate_actions'] = [
                    '立即修复所有严重漏洞',
                    '加强访问控制',
                    '更新安全策略'
                ];
            }
            
            if ($riskLevel >= 0.6) {
                $recommendations['short_term'] = [
                    '实施安全补丁',
                    '加强监控',
                    '进行安全培训'
                ];
            }
            
            $recommendations['long_term'] = [
                '建立安全基线',
                '实施持续监控',
                '定期安全评估'
            ];
            
            return $recommendations;
        } catch (\Exception $e) {
            $this->logger->error('获取缓解建议失败：' . $e->getMessage());
            return [];
        }
    }
} 