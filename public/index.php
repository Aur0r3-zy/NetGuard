<?php
/**
 * 网络监控系统入口文件
 */

// 定义项目根目录
define('ROOT_PATH', dirname(__DIR__));

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('需要PHP 7.4.0或更高版本');
}

// 检查必要的PHP扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'redis', 'json', 'mbstring', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        die("缺少必要的PHP扩展: {$ext}");
    }
}

// 加载自动加载器
if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    die('请先运行 composer install 安装依赖');
}
require_once ROOT_PATH . '/vendor/autoload.php';

// 加载环境变量
try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->load();
} catch (Exception $e) {
    die('环境变量加载失败：' . $e->getMessage());
}

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

// 设置安全头
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';');

// 初始化数据库连接
try {
    $db = \Database\Database::getInstance();
} catch (Exception $e) {
    error_log("数据库连接失败：" . $e->getMessage());
    die("系统暂时无法访问，请稍后再试。");
}

// 初始化日志系统
try {
    $logger = new \Monolog\Logger('network_monitor');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler(
        ROOT_PATH . '/storage/logs/app.log',
        \Monolog\Logger::DEBUG
    ));
    $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(
        ROOT_PATH . '/storage/logs/error.log',
        30,
        \Monolog\Logger::ERROR
    ));
} catch (Exception $e) {
    error_log("日志系统初始化失败：" . $e->getMessage());
}

// 初始化缓存系统
try {
    $cache = new \Predis\Client([
        'scheme' => 'tcp',
        'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port'   => $_ENV['REDIS_PORT'] ?? 6379,
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => $_ENV['REDIS_DATABASE'] ?? 0,
        'timeout' => 2.0,
        'read_write_timeout' => 2.0
    ]);
    $cache->ping();
} catch (Exception $e) {
    error_log("缓存系统连接失败：" . $e->getMessage());
    $cache = null;
}

// 初始化会话
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => $_ENV['SESSION_SECURE'] ?? true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => 3600,
    'use_strict_mode' => true,
    'use_only_cookies' => true
]);

// 请求预处理
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 中间件处理
$middlewares = [
    new \App\Api\Middleware\CorsMiddleware(),
    new \App\Api\Middleware\AuthMiddleware(),
    new \App\Api\Middleware\RateLimitMiddleware(),
    new \App\Api\Middleware\SecurityMiddleware()
];

foreach ($middlewares as $middleware) {
    try {
        $middleware->handle($request, $method);
    } catch (Exception $e) {
        $logger->error("中间件处理错误：" . $e->getMessage());
        http_response_code(500);
        require ROOT_PATH . '/resources/views/500.php';
        exit;
    }
}

// 路由处理
try {
    $router = new \App\Api\Router();
    
    // 加载API路由
    require ROOT_PATH . '/src/Api/routes.php';
    
    // 处理请求
    $response = $router->dispatch($request, $method);
    
    // 发送响应
    if (is_array($response)) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo $response;
    }
} catch (\App\Api\Exception\RouteNotFoundException $e) {
    $logger->warning("路由未找到：" . $e->getMessage());
    http_response_code(404);
    require ROOT_PATH . '/resources/views/404.php';
} catch (Exception $e) {
    $logger->error("路由处理错误：" . $e->getMessage());
    http_response_code(500);
    require ROOT_PATH . '/resources/views/500.php';
}

// 清理资源
if (isset($db)) {
    $db->close();
}
if (isset($cache)) {
    $cache->close();
} 