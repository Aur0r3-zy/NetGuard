<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Database\Database;
use Database\Migrations\CreateTables;
use Database\Seeds\InitData;

try {
    // 获取数据库连接
    $db = Database::getInstance();
    
    // 创建表
    echo "开始创建数据库表...\n";
    $migration = new CreateTables($db->getConnection());
    $migration->up();
    echo "数据库表创建完成\n";
    
    // 初始化数据
    echo "开始初始化数据...\n";
    $seed = new InitData($db->getConnection());
    $seed->run();
    echo "数据初始化完成\n";
    
    echo "数据库初始化成功完成！\n";
} catch (Exception $e) {
    echo "数据库初始化失败：" . $e->getMessage() . "\n";
    exit(1);
} 