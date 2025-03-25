<?php
// 获取当前配置
$config = [
    'monitor_interval' => $_ENV['MONITOR_INTERVAL'] ?? 60,
    'alert_threshold' => $_ENV['ALERT_THRESHOLD'] ?? 80,
    'max_log_size' => $_ENV['MAX_LOG_SIZE'] ?? 10485760,
    'max_log_files' => $_ENV['MAX_LOG_FILES'] ?? 30,
    'blacklist_enabled' => $_ENV['BLACKLIST_ENABLED'] ?? true,
    'whitelist_enabled' => $_ENV['WHITELIST_ENABLED'] ?? true,
    'email_notification' => $_ENV['EMAIL_NOTIFICATION'] ?? false,
    'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
    'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
    'smtp_user' => $_ENV['SMTP_USER'] ?? '',
    'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
    'notification_email' => $_ENV['NOTIFICATION_EMAIL'] ?? ''
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 更新环境变量
        $env_file = ROOT_PATH . '/.env';
        $env_content = file_get_contents($env_file);
        
        foreach ($_POST as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $env_content)) {
                $env_content = preg_replace($pattern, $replacement, $env_content);
            } else {
                $env_content .= "\n{$replacement}";
            }
        }
        
        file_put_contents($env_file, $env_content);
        
        // 更新缓存
        $cache->del('system_config');
        
        $_SESSION['flash_message'] = '设置已更新';
        $_SESSION['flash_type'] = 'success';
        
        // 重定向到当前页面
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = '设置更新失败：' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// 开始输出缓冲
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">系统设置</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <!-- 监控设置 -->
                <h5 class="card-title mb-4">监控设置</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">监控间隔（秒）</label>
                            <input type="number" name="MONITOR_INTERVAL" class="form-control" value="<?php echo $config['monitor_interval']; ?>" min="30" max="3600">
                            <div class="form-text">设置系统检查网络流量的时间间隔，范围30-3600秒</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">告警阈值</label>
                            <input type="number" name="ALERT_THRESHOLD" class="form-control" value="<?php echo $config['alert_threshold']; ?>" min="0" max="100">
                            <div class="form-text">设置触发告警的风险阈值，范围0-100</div>
                        </div>
                    </div>
                </div>
                
                <!-- 日志设置 -->
                <h5 class="card-title mb-4">日志设置</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">最大日志大小（字节）</label>
                            <input type="number" name="MAX_LOG_SIZE" class="form-control" value="<?php echo $config['max_log_size']; ?>" min="1048576">
                            <div class="form-text">单个日志文件的最大大小，最小1MB</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">最大日志文件数</label>
                            <input type="number" name="MAX_LOG_FILES" class="form-control" value="<?php echo $config['max_log_files']; ?>" min="1" max="100">
                            <div class="form-text">保留的日志文件数量，范围1-100</div>
                        </div>
                    </div>
                </div>
                
                <!-- 安全设置 -->
                <h5 class="card-title mb-4">安全设置</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="BLACKLIST_ENABLED" class="form-check-input" <?php echo $config['blacklist_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">启用黑名单</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="WHITELIST_ENABLED" class="form-check-input" <?php echo $config['whitelist_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">启用白名单</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 通知设置 -->
                <h5 class="card-title mb-4">通知设置</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="EMAIL_NOTIFICATION" class="form-check-input" <?php echo $config['email_notification'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">启用邮件通知</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">通知邮箱</label>
                            <input type="email" name="NOTIFICATION_EMAIL" class="form-control" value="<?php echo $config['notification_email']; ?>">
                            <div class="form-text">接收告警通知的邮箱地址</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SMTP服务器</label>
                            <input type="text" name="SMTP_HOST" class="form-control" value="<?php echo $config['smtp_host']; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SMTP端口</label>
                            <input type="number" name="SMTP_PORT" class="form-control" value="<?php echo $config['smtp_port']; ?>" min="1" max="65535">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SMTP用户名</label>
                            <input type="text" name="SMTP_USER" class="form-control" value="<?php echo $config['smtp_user']; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SMTP密码</label>
                            <input type="password" name="SMTP_PASS" class="form-control" value="<?php echo $config['smtp_pass']; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- 提交按钮 -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 