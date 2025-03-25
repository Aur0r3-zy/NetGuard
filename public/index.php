<?php
/**
 * 网络监控系统入口文件
 */

// 定义项目根目录
define('ROOT_PATH', dirname(__DIR__));

// 加载自动加载器
require_once ROOT_PATH . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// 设置错误报告
if ($_ENV['APP_DEBUG'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 初始化数据库连接
try {
    $db = \Database\Database::getInstance();
} catch (Exception $e) {
    error_log("数据库连接失败：" . $e->getMessage());
    die("系统暂时无法访问，请稍后再试。");
}

// 初始化日志系统
$logger = new \Monolog\Logger('network_monitor');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(
    ROOT_PATH . '/storage/logs/app.log',
    \Monolog\Logger::DEBUG
));

// 初始化缓存系统
$cache = new \Predis\Client([
    'scheme' => 'tcp',
    'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port'   => $_ENV['REDIS_PORT'] ?? 6379,
]);

// 初始化会话
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => $_ENV['SESSION_SECURE'] ?? true,
    'cookie_samesite' => 'Strict'
]);

// 路由处理
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// 简单的路由系统
try {
    switch ($request) {
        case '/':
            require ROOT_PATH . '/resources/views/dashboard.php';
            break;
        case '/monitor':
            require ROOT_PATH . '/resources/views/monitor.php';
            break;
        case '/alerts':
            require ROOT_PATH . '/resources/views/alerts.php';
            break;
        case '/settings':
            require ROOT_PATH . '/resources/views/settings.php';
            break;
        default:
            http_response_code(404);
            require ROOT_PATH . '/resources/views/404.php';
            break;
    }
} catch (Exception $e) {
    $logger->error("路由处理错误：" . $e->getMessage());
    http_response_code(500);
    require ROOT_PATH . '/resources/views/500.php';
} 