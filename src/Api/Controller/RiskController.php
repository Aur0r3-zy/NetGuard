<?php

namespace Api\Controller;

use Core\Risk\RiskAssessor;

class RiskController {
    private $riskAssessor;
    
    public function __construct(RiskAssessor $riskAssessor) {
        $this->riskAssessor = $riskAssessor;
    }
    
    public function scanVulnerabilities() {
        try {
            $result = $this->riskAssessor->scanVulnerabilities();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '扫描成功',
                    'data' => $result['data']
                ];
            }
            
            return [
                'code' => 500,
                'message' => $result['message']
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => '漏洞扫描失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getAssessment() {
        try {
            $result = $this->riskAssessor->scanVulnerabilities();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'vulnerabilities' => $result['data']['vulnerabilities'],
                        'threats' => $result['data']['threats'],
                        'report' => $result['data']['report']
                    ]
                ];
            }
            
            return [
                'code' => 500,
                'message' => $result['message']
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => '获取评估报告失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRiskScore() {
        try {
            $result = $this->riskAssessor->scanVulnerabilities();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'risk_score' => $result['data']['risk_score']
                    ]
                ];
            }
            
            return [
                'code' => 500,
                'message' => $result['message']
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => '获取风险评分失败：' . $e->getMessage()
            ];
        }
    }
} 