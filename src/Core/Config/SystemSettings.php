<?php

namespace Core\Config;

class SystemSettings {
    private $db;
    private $logger;
    private $cache;
    
    public function __construct($db, $logger, $cache) {
        $this->db = $db;
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    public function getSettings() {
        try {
            // 尝试从缓存获取
            $settings = $this->cache->get('system_settings');
            if ($settings !== false) {
                return [
                    'status' => 'success',
                    'data' => $settings
                ];
            }
            
            // 从数据库获取
            $query = "SELECT * FROM system_settings";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['key']] = $row['value'];
            }
            
            // 缓存设置
            $this->cache->set('system_settings', $settings, 3600);
            
            return [
                'status' => 'success',
                'data' => $settings
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取系统设置失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '获取系统设置失败：' . $e->getMessage()
            ];
        }
    }
    
    public function updateSettings($settings) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO system_settings (key, value, updated_at) 
                         VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE value = ?, updated_at = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $key,
                    $value,
                    time(),
                    $value,
                    time()
                ]);
            }
            
            $this->db->commit();
            
            // 清除缓存
            $this->cache->delete('system_settings');
            
            return [
                'status' => 'success',
                'message' => '系统设置更新成功'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('更新系统设置失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '更新系统设置失败：' . $e->getMessage()
            ];
        }
    }
    
    public function getSetting($key, $default = null) {
        try {
            $settings = $this->getSettings();
            
            if ($settings['status'] === 'success') {
                return $settings['data'][$key] ?? $default;
            }
            
            return $default;
        } catch (\Exception $e) {
            $this->logger->error('获取系统设置失败：' . $e->getMessage());
            return $default;
        }
    }
    
    public function updateSetting($key, $value) {
        return $this->updateSettings([$key => $value]);
    }
    
    public function resetSettings() {
        try {
            $query = "DELETE FROM system_settings";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            // 清除缓存
            $this->cache->delete('system_settings');
            
            return [
                'status' => 'success',
                'message' => '系统设置已重置'
            ];
        } catch (\Exception $e) {
            $this->logger->error('重置系统设置失败：' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => '重置系统设置失败：' . $e->getMessage()
            ];
        }
    }
    
    public function validateSettings($settings) {
        $errors = [];
        
        // 验证日志级别
        if (isset($settings['log_level'])) {
            $validLevels = ['debug', 'info', 'warning', 'error', 'critical'];
            if (!in_array($settings['log_level'], $validLevels)) {
                $errors[] = '无效的日志级别';
            }
        }
        
        // 验证数据库连接设置
        if (isset($settings['db_host'])) {
            if (empty($settings['db_host'])) {
                $errors[] = '数据库主机不能为空';
            }
        }
        
        if (isset($settings['db_port'])) {
            if (!is_numeric($settings['db_port']) || $settings['db_port'] < 1 || $settings['db_port'] > 65535) {
                $errors[] = '无效的数据库端口';
            }
        }
        
        // 验证缓存设置
        if (isset($settings['cache_ttl'])) {
            if (!is_numeric($settings['cache_ttl']) || $settings['cache_ttl'] < 0) {
                $errors[] = '无效的缓存过期时间';
            }
        }
        
        // 验证安全设置
        if (isset($settings['max_login_attempts'])) {
            if (!is_numeric($settings['max_login_attempts']) || $settings['max_login_attempts'] < 1) {
                $errors[] = '无效的最大登录尝试次数';
            }
        }
        
        if (isset($settings['session_timeout'])) {
            if (!is_numeric($settings['session_timeout']) || $settings['session_timeout'] < 0) {
                $errors[] = '无效的会话超时时间';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function getDefaultSettings() {
        return [
            'log_level' => 'info',
            'db_host' => 'localhost',
            'db_port' => 3306,
            'db_name' => 'security_system',
            'db_user' => 'root',
            'db_pass' => '',
            'cache_ttl' => 3600,
            'max_login_attempts' => 5,
            'session_timeout' => 1800,
            'enable_notifications' => true,
            'notification_email' => '',
            'maintenance_mode' => false,
            'debug_mode' => false,
            'timezone' => 'Asia/Shanghai',
            'language' => 'zh_CN'
        ];
    }
} 