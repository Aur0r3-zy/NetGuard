<?php

namespace App\Services;

use Doctrine\DBAL\Connection;

class PermissionService {
    private $db;

    public function __construct(Connection $db) {
        $this->db = $db;
    }

    public function getAllPermissions(): array {
        $sql = "SELECT * FROM permissions ORDER BY name ASC";
        return $this->db->fetchAllAssociative($sql);
    }

    public function createPermission(array $data): array {
        $this->db->insert('permissions', [
            'name' => $data['name'],
            'description' => $data['description'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $id = $this->db->lastInsertId();
        return $this->getPermissionById($id);
    }

    public function deletePermission(int $id): void {
        // 首先删除用户-权限关联
        $this->db->delete('user_permissions', ['permission_id' => $id]);
        // 然后删除权限
        $this->db->delete('permissions', ['id' => $id]);
    }

    public function assignPermissionsToUser(int $userId, array $permissionIds): void {
        // 开启事务
        $this->db->beginTransaction();

        try {
            // 删除用户现有权限
            $this->db->delete('user_permissions', ['user_id' => $userId]);

            // 添加新权限
            foreach ($permissionIds as $permissionId) {
                $this->db->insert('user_permissions', [
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserPermissions(int $userId): array {
        $sql = "SELECT p.* FROM permissions p
                INNER JOIN user_permissions up ON p.id = up.permission_id
                WHERE up.user_id = ?
                ORDER BY p.name ASC";
        
        return $this->db->fetchAllAssociative($sql, [$userId]);
    }

    private function getPermissionById(int $id): array {
        return $this->db->fetchAssociative("SELECT * FROM permissions WHERE id = ?", [$id]);
    }
} 