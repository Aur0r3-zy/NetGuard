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
     * 获取storage目录的完整路径
     *
     * @param string $path 相对路径
     * @return string
     */
    function storage_path($path = '') {
        return __DIR__ . '/storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    /**
     * 获取database目录的完整路径
     *
     * @param string $path 相对路径
     * @return string
     */
    function database_path($path = '') {
        return __DIR__ . '/database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
} 