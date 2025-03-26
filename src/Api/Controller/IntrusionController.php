<?php

namespace App\Api\Controller;

use App\Core\Data\IntrusionRecord;
use App\Core\Data\IntrusionStatistics;
use Core\Data\IntrusionTags;
use Core\Data\IntrusionComments;
use App\Core\Data\SecurityMonitor;
use App\Core\Logger\Logger;

class IntrusionController {
    private $intrusionRecord;
    private $intrusionStatistics;
    private $intrusionTags;
    private $intrusionComments;
    private IntrusionStatistics $intrusionStats;
    private SecurityMonitor $securityMonitor;
    private Logger $logger;
    
    public function __construct(
        IntrusionRecord $intrusionRecord,
        IntrusionStatistics $intrusionStatistics,
        IntrusionTags $intrusionTags,
        IntrusionComments $intrusionComments
    ) {
        $this->intrusionRecord = $intrusionRecord;
        $this->intrusionStatistics = $intrusionStatistics;
        $this->intrusionTags = $intrusionTags;
        $this->intrusionComments = $intrusionComments;
        $this->logger = new Logger();
        $this->intrusionStats = new IntrusionStatistics($this->logger);
        $this->securityMonitor = new SecurityMonitor();
    }
    
    public function getRecords()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $pageSize = $_GET['pageSize'] ?? 10;
            $sourceIp = $_GET['sourceIp'] ?? '';
            $targetIp = $_GET['targetIp'] ?? '';
            $attackType = $_GET['attackType'] ?? '';
            $startTime = $_GET['startTime'] ?? '';
            $endTime = $_GET['endTime'] ?? '';

            $records = $this->intrusionRecord->getRecords(
                $page,
                $pageSize,
                $sourceIp,
                $targetIp,
                $attackType,
                $startTime,
                $endTime
            );

            return [
                'code' => 200,
                'message' => 'success',
                'data' => $records
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getRecord($id)
    {
        try {
            $record = $this->intrusionRecord->getRecord($id);
            if (!$record) {
                return [
                    'code' => 404,
                    'message' => '记录不存在'
                ];
            }

            return [
                'code' => 200,
                'message' => 'success',
                'data' => $record
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function createRecord()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $this->intrusionRecord->createRecord($data);
            
            // 更新统计数据
            $this->intrusionStatistics->updateStatistics(date('Y-m-d'));

            return [
                'code' => 200,
                'message' => 'success',
                'data' => ['id' => $id]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function updateRecord($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $this->intrusionRecord->updateRecord($id, $data);
            
            if (!$success) {
                return [
                    'code' => 404,
                    'message' => '记录不存在'
                ];
            }

            // 更新统计数据
            $this->intrusionStatistics->updateStatistics(date('Y-m-d'));

            return [
                'code' => 200,
                'message' => 'success'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function deleteRecord($id)
    {
        try {
            $success = $this->intrusionRecord->deleteRecord($id);
            
            if (!$success) {
                return [
                    'code' => 404,
                    'message' => '记录不存在'
                ];
            }

            // 更新统计数据
            $this->intrusionStatistics->updateStatistics(date('Y-m-d'));

            return [
                'code' => 200,
                'message' => 'success'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getAttackTypes()
    {
        try {
            $types = $this->intrusionRecord->getAttackTypes();
            return [
                'code' => 200,
                'message' => 'success',
                'data' => $types
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function exportRecords()
    {
        try {
            $sourceIp = $_GET['sourceIp'] ?? '';
            $targetIp = $_GET['targetIp'] ?? '';
            $attackType = $_GET['attackType'] ?? '';
            $startTime = $_GET['startTime'] ?? '';
            $endTime = $_GET['endTime'] ?? '';

            $records = $this->intrusionRecord->getAllRecords(
                $sourceIp,
                $targetIp,
                $attackType,
                $startTime,
                $endTime
            );

            // 设置响应头
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=入侵记录.csv');

            // 创建CSV文件
            $output = fopen('php://output', 'w');
            
            // 写入表头
            fputcsv($output, ['入侵时间', '攻击源IP', '目标IP', '攻击类型', '严重程度', '处理状态', '描述']);

            // 写入数据
            foreach ($records as $record) {
                fputcsv($output, [
                    $record['time'],
                    $record['source_ip'],
                    $record['target_ip'],
                    $record['attack_type'],
                    $record['severity'],
                    $record['status'],
                    $record['description']
                ]);
            }

            fclose($output);
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getStatistics($startDate = null, $endDate = null) {
        try {
            $result = $this->intrusionStatistics->getStatistics($startDate, $endDate);
            
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
                'message' => '获取统计数据失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getTags() {
        try {
            $result = $this->intrusionTags->getAllTags();
            
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
                'message' => '获取标签列表失败：' . $e->getMessage()
            ];
        }
    }
    
    public function addComment($recordId, $userId, $content) {
        try {
            $result = $this->intrusionComments->addComment($recordId, $userId, $content);
            
            if ($result['status'] === 'success') {
                return [
                    'code' => 200,
                    'message' => '添加成功',
                    'data' => [
                        'comment_id' => $result['comment_id']
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
                'message' => '添加评论失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getRecordComments($recordId, $limit = 50, $offset = 0) {
        try {
            $result = $this->intrusionComments->getRecordComments($recordId, $limit, $offset);
            
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
                'message' => '获取评论失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取入侵检测统计数据
     * @return array
     */
    public function getStatistics(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'total_attacks' => $this->intrusionStats->getTotalAttacks(),
                    'today_attacks' => $this->intrusionStats->getTodayAttacks(),
                    'attack_types' => $this->intrusionStats->getAttackTypeDistribution(),
                    'attack_sources' => $this->intrusionStats->getAttackSources(),
                    'attack_targets' => $this->intrusionStats->getAttackTargets(),
                    'attack_trend' => $this->intrusionStats->getAttackTrend()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取入侵检测统计数据失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取最近的攻击事件
     * @param int $limit 限制返回数量
     * @return array
     */
    public function getRecentAttacks(int $limit = 10): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'attacks' => $this->intrusionStats->getRecentAttacks($limit),
                    'total_count' => $this->intrusionStats->getTotalAttacks()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取最近攻击事件失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取攻击类型分布
     * @return array
     */
    public function getAttackTypes(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'types' => $this->intrusionStats->getAttackTypeDistribution(),
                    'severity_levels' => $this->intrusionStats->getAttackSeverityDistribution()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取攻击类型分布失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取攻击源分析
     * @return array
     */
    public function getAttackSources(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'top_sources' => $this->intrusionStats->getTopAttackSources(),
                    'source_countries' => $this->intrusionStats->getAttackSourceCountries(),
                    'source_ips' => $this->intrusionStats->getAttackSourceIPs()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取攻击源分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取攻击目标分析
     * @return array
     */
    public function getAttackTargets(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'top_targets' => $this->intrusionStats->getTopAttackTargets(),
                    'target_services' => $this->intrusionStats->getTargetServices(),
                    'target_ports' => $this->intrusionStats->getTargetPorts()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取攻击目标分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取攻击趋势分析
     * @param string $period 时间周期（hour/day/week/month）
     * @return array
     */
    public function getAttackTrend(string $period = 'day'): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'trend' => $this->intrusionStats->getAttackTrend($period),
                    'period' => $period
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取攻击趋势分析失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取异常检测结果
     * @return array
     */
    public function getAnomalies(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'anomalies' => $this->intrusionStats->getAnomalies(),
                    'risk_level' => $this->intrusionStats->getCurrentRiskLevel(),
                    'recommendations' => $this->intrusionStats->getSecurityRecommendations()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取异常检测结果失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取安全建议
     * @return array
     */
    public function getRecommendations(): array {
        try {
            return [
                'status' => 'success',
                'data' => [
                    'immediate_actions' => $this->securityMonitor->getImmediateActions(),
                    'long_term_recommendations' => $this->securityMonitor->getLongTermRecommendations(),
                    'security_patches' => $this->securityMonitor->getSecurityPatches()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '获取安全建议失败：' . $e->getMessage()
            ];
        }
    }
} 