<?php

namespace App\Services;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogService {
    private $db;
    private $logger;

    public function __construct(Connection $db) {
        $this->db = $db;
        
        // 初始化Monolog
        $this->logger = new Logger('security');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/security.log', Logger::WARNING));
    }

    public function logActivity(?int $userId, string $action, string $description, ?string $ipAddress = null): void {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress
        ]);
    }

    public function logAttack(array $data): void {
        // 记录到数据库
        $this->db->insert('attack_logs', [
            'ip_address' => $data['ip_address'],
            'attack_type' => $data['attack_type'],
            'request_method' => $data['request_method'],
            'request_uri' => $data['request_uri'],
            'request_body' => $data['request_body'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'severity' => $data['severity'],
            'status' => 'detected'
        ]);

        // 记录到文件
        $logLevel = match ($data['severity']) {
            'low' => Logger::WARNING,
            'medium' => Logger::ERROR,
            'high' => Logger::CRITICAL,
            'critical' => Logger::EMERGENCY,
            default => Logger::WARNING
        };

        $this->logger->log($logLevel, '检测到潜在攻击', [
            'attack_type' => $data['attack_type'],
            'ip_address' => $data['ip_address'],
            'request_uri' => $data['request_uri']
        ]);
    }

    public function getActivityLogs(array $filters = []): array {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('al.*, u.username')
            ->from('activity_logs', 'al')
            ->leftJoin('al', 'users', 'u', 'u.id = al.user_id')
            ->orderBy('al.created_at', 'DESC');

        if (isset($filters['user_id'])) {
            $queryBuilder
                ->andWhere('al.user_id = :userId')
                ->setParameter('userId', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $queryBuilder
                ->andWhere('al.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if (isset($filters['date_from'])) {
            $queryBuilder
                ->andWhere('al.created_at >= :dateFrom')
                ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder
                ->andWhere('al.created_at <= :dateTo')
                ->setParameter('dateTo', $filters['date_to']);
        }

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    public function getAttackLogs(array $filters = []): array {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('attack_logs')
            ->orderBy('created_at', 'DESC');

        if (isset($filters['severity'])) {
            $queryBuilder
                ->andWhere('severity = :severity')
                ->setParameter('severity', $filters['severity']);
        }

        if (isset($filters['attack_type'])) {
            $queryBuilder
                ->andWhere('attack_type = :attackType')
                ->setParameter('attackType', $filters['attack_type']);
        }

        if (isset($filters['ip_address'])) {
            $queryBuilder
                ->andWhere('ip_address = :ipAddress')
                ->setParameter('ipAddress', $filters['ip_address']);
        }

        if (isset($filters['date_from'])) {
            $queryBuilder
                ->andWhere('created_at >= :dateFrom')
                ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder
                ->andWhere('created_at <= :dateTo')
                ->setParameter('dateTo', $filters['date_to']);
        }

        return $queryBuilder->execute()->fetchAllAssociative();
    }
} 