<?php

namespace App\Api\Controller;

use App\Core\Data\IntrusionStatistics;
use App\Core\Data\TrafficMonitor;
use App\Core\Data\SecurityMonitor;
use App\Core\Logger\Logger;

class DashboardController
{
    private IntrusionStatistics $intrusionStats;
    private TrafficMonitor $trafficMonitor;
    private SecurityMonitor $securityMonitor;
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->intrusionStats = new IntrusionStatistics($this->logger);
        $this->trafficMonitor = new TrafficMonitor();
        $this->securityMonitor = new SecurityMonitor();
    }

    /**
     * 获取仪表盘统计数据
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'intrusion' => [
                        'total_attacks' => $this->intrusionStats->getTotalAttacks(),
                        'today_attacks' => $this->intrusionStats->getTodayAttacks(),
                        'attack_types' => $this->intrusionStats->getAttackTypeDistribution(),
                        'recent_attacks' => $this->intrusionStats->getRecentAttacks()
                    ],
                    'traffic' => [
                        'total_packets' => $this->trafficMonitor->getTrafficData()['total_packets'],
                        'total_bytes' => $this->trafficMonitor->getTrafficData()['total_bytes'],
                        'unique_sources' => $this->trafficMonitor->getTrafficData()['unique_sources'],
                        'unique_destinations' => $this->trafficMonitor->getTrafficData()['unique_destinations'],
                        'anomalies' => $this->trafficMonitor->getAnomalies()
                    ],
                    'security' => [
                        'total_events' => $this->securityMonitor->getSecurityData()['total_events'],
                        'high_severity' => $this->securityMonitor->getSecurityData()['high_severity'],
                        'medium_severity' => $this->securityMonitor->getSecurityData()['medium_severity'],
                        'low_severity' => $this->securityMonitor->getSecurityData()['low_severity'],
                        'recent_events' => $this->securityMonitor->getAnomalies()
                    ],
                    'system' => [
                        'cpu_usage' => $this->getSystemCpuUsage(),
                        'memory_usage' => $this->getSystemMemoryUsage(),
                        'disk_usage' => $this->getSystemDiskUsage(),
                        'network_status' => $this->getNetworkStatus()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取统计数据失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取系统CPU使用率
     * @return float
     */
    private function getSystemCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] * 100;
        }
        return 0.0;
    }

    /**
     * 获取系统内存使用率
     * @return float
     */
    private function getSystemMemoryUsage(): float
    {
        if (PHP_OS == 'Linux') {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            if (isset($total[1]) && isset($available[1])) {
                return (1 - $available[1] / $total[1]) * 100;
            }
        }
        return 0.0;
    }

    /**
     * 获取系统磁盘使用率
     * @return float
     */
    private function getSystemDiskUsage(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        if ($total > 0) {
            return (($total - $free) / $total) * 100;
        }
        return 0.0;
    }

    /**
     * 获取网络状态
     * @return array
     */
    private function getNetworkStatus(): array
    {
        return [
            'status' => 'normal',
            'latency' => $this->getNetworkLatency(),
            'bandwidth' => $this->getNetworkBandwidth(),
            'connections' => $this->getActiveConnections()
        ];
    }

    /**
     * 获取网络延迟
     * @return float
     */
    private function getNetworkLatency(): float
    {
        $start = microtime(true);
        @fsockopen('8.8.8.8', 53, $errno, $errstr, 1);
        return (microtime(true) - $start) * 1000;
    }

    /**
     * 获取网络带宽
     * @return array
     */
    private function getNetworkBandwidth(): array
    {
        return [
            'upload' => 0,
            'download' => 0
        ];
    }

    /**
     * 获取活动连接数
     * @return int
     */
    private function getActiveConnections(): int
    {
        if (PHP_OS == 'Linux') {
            $connections = shell_exec('netstat -an | grep ESTABLISHED | wc -l');
            return (int)$connections;
        }
        return 0;
    }
} 