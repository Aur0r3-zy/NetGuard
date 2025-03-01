<?php

namespace App\Services;

use Doctrine\DBAL\Connection;

class UserService {
    private $db;

    public function __construct(Connection $db) {
        $this->db = $db;
    }

    public function getAllUsers(): array {
        $queryBuilder = $this->db->createQueryBuilder();
        return $queryBuilder
            ->select('u.*')
            ->from('users', 'u')
            ->execute()
            ->fetchAllAssociative();
    }

    public function getUserById(int $id): ?array {
        $queryBuilder = $this->db->createQueryBuilder();
        $result = $queryBuilder
            ->select('u.*')
            ->from('users', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetchAssociative();
        
        return $result ?: null;
    }

    public function createUser(array $data): array {
        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $this->db->insert('users', [
            'username' => $data['username'],
            'password' => $data['password'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'user',
            'status' => 1
        ]);

        $id = $this->db->lastInsertId();
        return $this->getUserById($id);
    }

    public function updateUser(int $id, array $data): array {
        $updateData = [];
        
        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, ['id' => $id]);
        }

        return $this->getUserById($id);
    }

    public function deleteUser(int $id): bool {
        return $this->db->delete('users', ['id' => $id]) > 0;
    }

    public function validateCredentials(string $username, string $password): ?array {
        $user = $this->db->createQueryBuilder()
            ->select('u.*')
            ->from('users', 'u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->execute()
            ->fetchAssociative();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    public function getUserPermissions(int $userId): array {
        return $this->db->createQueryBuilder()
            ->select('p.*')
            ->from('permissions', 'p')
            ->join('p', 'user_permissions', 'up', 'up.permission_id = p.id')
            ->where('up.user_id = :userId')
            ->setParameter('userId', $userId)
            ->execute()
            ->fetchAllAssociative();
    }
} 