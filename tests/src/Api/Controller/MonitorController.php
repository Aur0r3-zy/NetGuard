<?php

namespace App\Api\Controller;

use App\Core\Monitor\AttackMonitor;
use App\Core\Monitor\TrafficMonitor;
use App\Core\Monitor\SecurityMonitor;

class MonitorController {
    private $attackMonitor;
    private $trafficMonitor;
    private $securityMonitor;
    
    public function __construct() {
        $this->attackMonitor = new AttackMonitor();
        $this->trafficMonitor = new TrafficMonitor();
        $this->securityMonitor = new SecurityMonitor();
    }
    
    public function getAttackData() {
        try {
            $data = $this->attackMonitor->getAttackData();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getTrafficData() {
        try {
            $data = $this->trafficMonitor->getTrafficData();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getSecurityData() {
        try {
            $data = $this->securityMonitor->getSecurityData();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getAnomalies() {
        try {
            $data = $this->securityMonitor->getAnomalies();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
} 