<?php

namespace App\Api\Controller;

use App\Core\Risk\RiskAssessor;
use App\Core\Risk\VulnerabilityScanner;

class RiskController {
    private $riskAssessor;
    private $vulnerabilityScanner;
    
    public function __construct() {
        $this->riskAssessor = new RiskAssessor();
        $this->vulnerabilityScanner = new VulnerabilityScanner();
    }
    
    public function scanVulnerabilities() {
        try {
            $result = $this->vulnerabilityScanner->scan();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getAssessment() {
        try {
            $assessment = $this->riskAssessor->getAssessment();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $assessment
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getRiskScore() {
        try {
            $score = $this->riskAssessor->getRiskScore();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'score' => $score
                ]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
} 