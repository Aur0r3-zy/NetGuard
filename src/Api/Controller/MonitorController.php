<?php

namespace Api\Controller;

use Core\Monitor\AttackMonitor;
use Core\Monitor\TrafficMonitor;
use Core\Monitor\SecurityMonitor;

class MonitorController {
    private $attackMonitor;
    private $trafficMonitor;
    private $securityMonitor;
    
    public function __construct(
        AttackMonitor $attackMonitor,
        TrafficMonitor $trafficMonitor,
        SecurityMonitor $securityMonitor
    ) {
        $this->attackMonitor = $attackMonitor;
        $this->trafficMonitor = $trafficMonitor;
        $this->securityMonitor = $securityMonitor;
    }
    
    public function getAttackData() {
        try {
            $result = $this->attackMonitor->monitorTraffic();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
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
                'message' => '获取攻击数据失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getTrafficData() {
        try {
            $result = $this->trafficMonitor->monitorTraffic();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
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
                'message' => '获取流量数据失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getSecurityData() {
        try {
            $result = $this->securityMonitor->monitorSecurity();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
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
                'message' => '获取安全数据失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getAnomalies() {
        try {
            $result = $this->trafficMonitor->monitorTraffic();
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => $result['data']['anomalies']
                ];
            }
            
            return [
                'code' => 500,
                'message' => $result['message']
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => '获取异常数据失败：' . $e->getMessage()
            ];
        }
    }
} 