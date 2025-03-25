<?php

if (!function_exists('env')) {
    /**
     * 获取环境变量值
     *
     * @param string $key 环境变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    function env($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
        
        return $value;
    }
}

if (!function_exists('storage_path')) {
    /**
     * 获取存储目录的完整路径
     *
     * @param string $path 相对路径
     * @return string
     */
    function storage_path($path = '') {
        $basePath = __DIR__ . '/storage';
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    /**
     * 获取数据库目录的完整路径
     *
     * @param string $path 相对路径
     * @return string
     */
    function database_path($path = '') {
        $basePath = __DIR__ . '/database';
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

// 确保必要的目录存在
$directories = [
    storage_path(),
    storage_path('logs'),
    storage_path('backups'),
    storage_path('backups/database'),
    database_path(),
    database_path('migrations'),
    database_path('seeds'),
];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
} 