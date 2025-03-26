<?php

namespace App\Api\Controller;

use App\Core\Data\SecurityMonitor;
use App\Core\Data\RiskAssessment;
use App\Core\Logger\Logger;

class RiskController {
    private SecurityMonitor $securityMonitor;
    private RiskAssessment $riskAssessment;
    private Logger $logger;
    
    public function __construct() {
        $this->logger = new Logger();
        $this->securityMonitor = new SecurityMonitor();
        $this->riskAssessment = new RiskAssessment($this->logger);
    }
    
    /**
     * 获取风险评估结果
     * @return array
     */
    public function getRiskAssessment(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'overall_risk' => $this->riskAssessment->getOverallRisk(),
                    'risk_components' => $this->riskAssessment->getRiskComponents(),
                    'risk_trend' => $this->riskAssessment->getRiskTrend(),
                    'risk_factors' => $this->riskAssessment->getRiskFactors()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取风险评估结果失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取风险组件分析
     * @return array
     */
    public function getRiskComponents(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'network_risk' => $this->riskAssessment->getNetworkRisk(),
                    'system_risk' => $this->riskAssessment->getSystemRisk(),
                    'application_risk' => $this->riskAssessment->getApplicationRisk(),
                    'data_risk' => $this->riskAssessment->getDataRisk(),
                    'user_risk' => $this->riskAssessment->getUserRisk()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取风险组件分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取风险趋势分析
     * @param string $period 时间周期（day/week/month）
     * @return array
     */
    public function getRiskTrend(string $period = 'week'): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'trend' => $this->riskAssessment->getRiskTrend($period),
                    'period' => $period
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取风险趋势分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取风险因素分析
     * @return array
     */
    public function getRiskFactors(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'vulnerabilities' => $this->riskAssessment->getVulnerabilities(),
                    'threats' => $this->riskAssessment->getThreats(),
                    'impacts' => $this->riskAssessment->getImpacts(),
                    'controls' => $this->riskAssessment->getControls()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取风险因素分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取漏洞分析
     * @return array
     */
    public function getVulnerabilities(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'total_vulnerabilities' => $this->riskAssessment->getTotalVulnerabilities(),
                    'critical_vulnerabilities' => $this->riskAssessment->getCriticalVulnerabilities(),
                    'high_vulnerabilities' => $this->riskAssessment->getHighVulnerabilities(),
                    'medium_vulnerabilities' => $this->riskAssessment->getMediumVulnerabilities(),
                    'low_vulnerabilities' => $this->riskAssessment->getLowVulnerabilities(),
                    'vulnerability_trend' => $this->riskAssessment->getVulnerabilityTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取漏洞分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取威胁分析
     * @return array
     */
    public function getThreats(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'active_threats' => $this->riskAssessment->getActiveThreats(),
                    'threat_categories' => $this->riskAssessment->getThreatCategories(),
                    'threat_sources' => $this->riskAssessment->getThreatSources(),
                    'threat_trend' => $this->riskAssessment->getThreatTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取威胁分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取影响分析
     * @return array
     */
    public function getImpacts(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'business_impact' => $this->riskAssessment->getBusinessImpact(),
                    'financial_impact' => $this->riskAssessment->getFinancialImpact(),
                    'operational_impact' => $this->riskAssessment->getOperationalImpact(),
                    'reputation_impact' => $this->riskAssessment->getReputationImpact()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取影响分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取控制措施分析
     * @return array
     */
    public function getControls(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'preventive_controls' => $this->riskAssessment->getPreventiveControls(),
                    'detective_controls' => $this->riskAssessment->getDetectiveControls(),
                    'corrective_controls' => $this->riskAssessment->getCorrectiveControls(),
                    'control_effectiveness' => $this->riskAssessment->getControlEffectiveness()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取控制措施分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取风险缓解建议
     * @return array
     */
    public function getMitigationRecommendations(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'immediate_actions' => $this->riskAssessment->getImmediateActions(),
                    'short_term_recommendations' => $this->riskAssessment->getShortTermRecommendations(),
                    'long_term_recommendations' => $this->riskAssessment->getLongTermRecommendations(),
                    'resource_requirements' => $this->riskAssessment->getResourceRequirements()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取风险缓解建议失败：' . $e->getMessage()
            ];
        }
    }
} 