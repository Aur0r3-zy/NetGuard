<?php

namespace App\Services;

use Doctrine\DBAL\Connection;

class AttackLogService {
    private $db;

    public function __construct(Connection $db) {
        $this->db = $db;
    }

    public function getAttackLogs(int $page, int $limit, array $filters = []): array {
        $offset = ($page - 1) * $limit;
        
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('attack_logs')
            ->orderBy('created_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // 应用过滤条件
        if (!empty($filters['attack_type'])) {
            $queryBuilder->andWhere('attack_type = :attack_type')
                ->setParameter('attack_type', $filters['attack_type']);
        }
        
        if (!empty($filters['severity'])) {
            $queryBuilder->andWhere('severity = :severity')
                ->setParameter('severity', $filters['severity']);
        }
        
        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('status = :status')
                ->setParameter('status', $filters['status']);
        }
        
        if (!empty($filters['ip_address'])) {
            $queryBuilder->andWhere('ip_address LIKE :ip_address')
                ->setParameter('ip_address', '%' . $filters['ip_address'] . '%');
        }
        
        if (!empty($filters['start_date'])) {
            $queryBuilder->andWhere('created_at >= :start_date')
                ->setParameter('start_date', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $queryBuilder->andWhere('created_at <= :end_date')
                ->setParameter('end_date', $filters['end_date']);
        }

        // 获取总记录数
        $countBuilder = clone $queryBuilder;
        $total = $countBuilder->select('COUNT(*) as cnt')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->execute()
            ->fetchOne();

        // 获取数据
        $data = $queryBuilder->execute()->fetchAllAssociative();

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    public function getStatistics(string $timeRange): array {
        $stats = [];
        
        // 根据时间范围设置查询条件
        switch ($timeRange) {
            case 'week':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            default: // day
                $startDate = date('Y-m-d');
        }

        // 获取攻击类型分布
        $attackTypes = $this->db->createQueryBuilder()
            ->select('attack_type, COUNT(*) as count')
            ->from('attack_logs')
            ->where('DATE(created_at) >= :start_date')
            ->setParameter('start_date', $startDate)
            ->groupBy('attack_type')
            ->execute()
            ->fetchAllAssociative();

        // 获取严重程度分布
        $severityDistribution = $this->db->createQueryBuilder()
            ->select('severity, COUNT(*) as count')
            ->from('attack_logs')
            ->where('DATE(created_at) >= :start_date')
            ->setParameter('start_date', $startDate)
            ->groupBy('severity')
            ->execute()
            ->fetchAllAssociative();

        // 获取每日攻击趋势
        $dailyTrend = $this->db->createQueryBuilder()
            ->select('DATE(created_at) as date, COUNT(*) as count')
            ->from('attack_logs')
            ->where('DATE(created_at) >= :start_date')
            ->setParameter('start_date', $startDate)
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->execute()
            ->fetchAllAssociative();

        $stats['attack_types'] = $attackTypes;
        $stats['severity_distribution'] = $severityDistribution;
        $stats['daily_trend'] = $dailyTrend;
        
        // 计算总攻击次数
        $stats['total_attacks'] = array_sum(array_column($dailyTrend, 'count'));
        
        // 计算高危攻击数量
        $stats['high_risk_attacks'] = $this->db->createQueryBuilder()
            ->select('COUNT(*) as count')
            ->from('attack_logs')
            ->where('severity IN (:severities)')
            ->andWhere('DATE(created_at) >= :start_date')
            ->setParameter('severities', ['high', 'critical'], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->setParameter('start_date', $startDate)
            ->execute()
            ->fetchOne();

        return $stats;
    }

    public function getAttackLogDetails(int $id): ?array {
        return $this->db->createQueryBuilder()
            ->select('*')
            ->from('attack_logs')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetchAssociative() ?: null;
    }

    public function updateAttackLogStatus(int $id, string $status): void {
        $this->db->update('attack_logs', 
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $id]
        );
    }

    public function exportAttackLogs(array $filters = []): array {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('attack_logs')
            ->orderBy('created_at', 'DESC');

        // 应用过滤条件
        if (!empty($filters['attack_type'])) {
            $queryBuilder->andWhere('attack_type = :attack_type')
                ->setParameter('attack_type', $filters['attack_type']);
        }
        
        if (!empty($filters['severity'])) {
            $queryBuilder->andWhere('severity = :severity')
                ->setParameter('severity', $filters['severity']);
        }
        
        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('status = :status')
                ->setParameter('status', $filters['status']);
        }
        
        if (!empty($filters['start_date'])) {
            $queryBuilder->andWhere('created_at >= :start_date')
                ->setParameter('start_date', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $queryBuilder->andWhere('created_at <= :end_date')
                ->setParameter('end_date', $filters['end_date']);
        }

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    public function logAttack(array $data): void {
        $this->db->insert('attack_logs', [
            'attack_type' => $data['attack_type'],
            'severity' => $data['severity'],
            'ip_address' => $data['ip_address'],
            'request_uri' => $data['request_uri'],
            'request_method' => $data['request_method'],
            'request_headers' => json_encode($data['request_headers']),
            'request_body' => $data['request_body'],
            'status' => 'detected',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
} 