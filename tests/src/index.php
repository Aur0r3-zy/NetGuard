<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Config\ConfigLoader;
use Core\Immune\Algorithm;
use Core\Network\TrafficAnalyzer;
use Data\Preprocessor;
use Utils\Logger;
use Utils\Database;

// 加载配置
$configLoader = new ConfigLoader();
$config = $configLoader->load();

// 初始化日志
$logger = new Logger(
    $config['logging']['file'],
    $config['logging']['max_size'],
    $config['logging']['backup_count'],
    $config['logging']['level']
);

try {
    // 初始化数据库连接
    $db = new Database(
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['name'],
        $config['database']['user'],
        $config['database']['password']
    );
    $db->connect();
    
    // 初始化核心组件
    $algorithm = new Algorithm(
        $config['algorithm']['threshold'],
        $config['algorithm']['memory_size'],
        $config['algorithm']['affinity_threshold'],
        $config['algorithm']['concentration_decay']
    );
    
    $trafficAnalyzer = new TrafficAnalyzer(
        $config['network']['packet_buffer_size'],
        $config['network']['protocols'],
        $config['network']['ports']
    );
    
    $preprocessor = new Preprocessor(
        $config['preprocessing']['normalization'],
        $config['preprocessing']['feature_extraction']
    );
    
    // 主循环
    while (true) {
        try {
            // 捕获网络流量
            $packets = $trafficAnalyzer->capturePackets();
            
            // 预处理数据
            $processedData = $preprocessor->preprocess($packets);
            
            // 运行免疫算法检测
            $results = $algorithm->analyze($processedData);
            
            // 记录检测结果
            foreach ($results as $result) {
                if ($result['is_attack']) {
                    $logger->warning("检测到潜在攻击", $result);
                    // 存储攻击记录
                    $db->insert('attack_records', [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'source_ip' => $result['source_ip'],
                        'target_ip' => $result['target_ip'],
                        'attack_type' => $result['attack_type'],
                        'confidence' => $result['confidence'],
                        'details' => json_encode($result['details'])
                    ]);
                }
            }
            
            // 休眠一段时间
            sleep(1);
            
        } catch (\Exception $e) {
            $logger->error("处理过程中发生错误: " . $e->getMessage());
            sleep(5); // 发生错误时等待较长时间
        }
    }
    
} catch (\Exception $e) {
    $logger->critical("程序初始化失败: " . $e->getMessage());
    exit(1);
} 