<?php

namespace App\Api\Controller;

use App\Core\Data\IntrusionRecord;
use App\Core\Data\IntrusionStatistics;
use Core\Data\IntrusionTags;
use Core\Data\IntrusionComments;

class IntrusionController {
    private $intrusionRecord;
    private $intrusionStatistics;
    private $intrusionTags;
    private $intrusionComments;
    
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
} 