<?php

namespace App\Api\Controller;

use App\Core\Data\TrafficMonitor;
use App\Core\Data\SecurityMonitor;
use App\Core\Data\IntrusionStatistics;
use App\Core\Logger\Logger;

class MonitorController {
    private TrafficMonitor $trafficMonitor;
    private SecurityMonitor $securityMonitor;
    private IntrusionStatistics $intrusionStats;
    private Logger $logger;
    
    public function __construct() {
        $this->logger = new Logger();
        $this->trafficMonitor = new TrafficMonitor();
        $this->securityMonitor = new SecurityMonitor();
        $this->intrusionStats = new IntrusionStatistics($this->logger);
    }
    
    /**
     * 获取攻击监控数据
     * @return array
     */
    public function getAttackData(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'total_attacks' => $this->intrusionStats->getTotalAttacks(),
                    'today_attacks' => $this->intrusionStats->getTodayAttacks(),
                    'attack_types' => $this->intrusionStats->getAttackTypeDistribution(),
                    'recent_attacks' => $this->intrusionStats->getRecentAttacks(),
                    'attack_trend' => $this->intrusionStats->getAttackTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取攻击数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取流量监控数据
     * @return array
     */
    public function getTrafficData(): array {
        try {
            $trafficData = $this->trafficMonitor->getTrafficData();
            return [
                'status' => 'success',
                'data' => [
                    'total_packets' => $trafficData['total_packets'],
                    'total_bytes' => $trafficData['total_bytes'],
                    'unique_sources' => $trafficData['unique_sources'],
                    'unique_destinations' => $trafficData['unique_destinations'],
                    'anomalies' => $this->trafficMonitor->getAnomalies(),
                    'traffic_trend' => $this->trafficMonitor->getTrafficTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取流量数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取安全监控数据
     * @return array
     */
    public function getSecurityData(): array {
        try {
            $securityData = $this->securityMonitor->getSecurityData();
            return [
                'status' => 'success',
                'data' => [
                    'total_events' => $securityData['total_events'],
                    'high_severity' => $securityData['high_severity'],
                    'medium_severity' => $securityData['medium_severity'],
                    'low_severity' => $securityData['low_severity'],
                    'recent_events' => $this->securityMonitor->getAnomalies(),
                    'event_trend' => $this->securityMonitor->getEventTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取安全数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取异常数据
     * @return array
     */
    public function getAnomalies(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'traffic_anomalies' => $this->trafficMonitor->getAnomalies(),
                    'security_anomalies' => $this->securityMonitor->getAnomalies(),
                    'intrusion_anomalies' => $this->intrusionStats->getAnomalies()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取异常数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取实时监控数据
     * @return array
     */
    public function getRealtimeData(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'current_connections' => $this->getCurrentConnections(),
                    'network_bandwidth' => $this->getNetworkBandwidth(),
                    'system_load' => $this->getSystemLoad(),
                    'active_threats' => $this->getActiveThreats()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取实时数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取当前连接数
     * @return int
     */
    private function getCurrentConnections(): int {
        if (PHP_OS == 'Linux') {
            $connections = shell_exec('netstat -an | grep ESTABLISHED | wc -l');
            return (int)$connections;
        }
        return 0;
    }
    
    /**
     * 获取网络带宽使用情况
     * @return array
     */
    private function getNetworkBandwidth(): array {
        if (PHP_OS == 'Linux') {
            $netstat = shell_exec('netstat -i');
            // 解析netstat输出获取带宽信息
            return [
                'upload' => 0,
                'download' => 0
            ];
        }
        return [
            'upload' => 0,
            'download' => 0
        ];
    }
    
    /**
     * 获取系统负载
     * @return array
     */
    private function getSystemLoad(): array {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2]
            ];
        }
        return [
            '1min' => 0,
            '5min' => 0,
            '15min' => 0
        ];
    }
    
    /**
     * 获取当前活动威胁
     * @return array
     */
    private function getActiveThreats(): array {
        return [
            'total' => 0,
            'high_risk' => 0,
            'medium_risk' => 0,
            'low_risk' => 0,
            'details' => []
        ];
    }
} 