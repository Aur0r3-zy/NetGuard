<?php

namespace App\Api\Controller;

use App\Core\Data\IntrusionStatistics;
use App\Core\Data\TrafficMonitor;
use App\Core\Data\SecurityMonitor;
use App\Core\Data\RiskAssessor;

class DashboardController
{
    private $intrusionStatistics;
    private $trafficMonitor;
    private $securityMonitor;
    private $riskAssessor;

    public function __construct()
    {
        $this->intrusionStatistics = new IntrusionStatistics();
        $this->trafficMonitor = new TrafficMonitor();
        $this->securityMonitor = new SecurityMonitor();
        $this->riskAssessor = new RiskAssessor();
    }

    public function getStatistics()
    {
        try {
            // 获取今日攻击次数
            $today = date('Y-m-d');
            $attackCount = $this->intrusionStatistics->getDailyCount($today);

            // 获取风险评分
            $riskScore = $this->riskAssessor->getCurrentRiskScore();

            // 获取异常流量数
            $anomalyCount = $this->trafficMonitor->getAnomalyCount();

            // 获取安全事件数
            $securityEvents = $this->securityMonitor->getEventCount();

            // 获取攻击类型分布
            $attackTypes = $this->intrusionStatistics->getAttackTypeDistribution();

            // 获取风险趋势
            $riskTrend = $this->riskAssessor->getRiskTrend();

            return [
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'statistics' => [
                        'attackCount' => $attackCount,
                        'riskScore' => $riskScore,
                        'anomalyCount' => $anomalyCount,
                        'securityEvents' => $securityEvents
                    ],
                    'attackTypes' => $attackTypes,
                    'riskTrend' => $riskTrend
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