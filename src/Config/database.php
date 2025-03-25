<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 数据库连接配置
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'network_security'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_PREFIX', 'ns_'),
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库安全配置
    |--------------------------------------------------------------------------
    */
    'security' => [
        // 数据库加密密钥
        'encryption_key' => env('DB_ENCRYPTION_KEY', 'your-secure-encryption-key'),
        
        // SSL连接配置
        'ssl' => [
            'verify' => true,
            'ca_path' => env('DB_SSL_CA_PATH', '/path/to/ca.pem'),
            'cert_path' => env('DB_SSL_CERT_PATH', '/path/to/client-cert.pem'),
            'key_path' => env('DB_SSL_KEY_PATH', '/path/to/client-key.pem'),
        ],
        
        // 敏感数据加密配置
        'encryption' => [
            'enabled' => true,
            'algorithm' => 'AES-256-CBC',
            'fields' => [
                'users.password',
                'api_keys.secret',
                'configs.value',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库性能配置
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // 连接池配置
        'pool' => [
            'min' => env('DB_POOL_MIN', 5),
            'max' => env('DB_POOL_MAX', 20),
            'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 60),
        ],
        
        // 查询缓存配置
        'cache' => [
            'enabled' => env('DB_CACHE_ENABLED', true),
            'ttl' => env('DB_CACHE_TTL', 300),
            'prefix' => env('DB_CACHE_PREFIX', 'db_cache_'),
        ],
        
        // 慢查询日志配置
        'slow_query' => [
            'enabled' => env('DB_SLOW_QUERY_ENABLED', true),
            'threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1.0), // 秒
            'log_path' => storage_path('logs/slow_query.log'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库备份配置
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('DB_BACKUP_ENABLED', true),
        'schedule' => env('DB_BACKUP_SCHEDULE', '0 2 * * *'), // 每天凌晨2点
        'retention' => env('DB_BACKUP_RETENTION', 7), // 保留7天
        'path' => storage_path('backups/database'),
        'compress' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库监控配置
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('DB_MONITORING_ENABLED', true),
        'metrics' => [
            'connection_count',
            'query_count',
            'slow_query_count',
            'error_count',
        ],
        'alert' => [
            'enabled' => true,
            'threshold' => [
                'connection_count' => 100,
                'slow_query_count' => 10,
                'error_count' => 5,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库维护配置
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        'optimize' => [
            'enabled' => true,
            'schedule' => '0 3 * * *', // 每天凌晨3点
        ],
        'analyze' => [
            'enabled' => true,
            'schedule' => '0 4 * * *', // 每天凌晨4点
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库错误处理配置
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        'log_errors' => true,
        'error_log_path' => storage_path('logs/db_errors.log'),
        'notify_on_error' => env('DB_NOTIFY_ON_ERROR', true),
        'notify_email' => env('DB_NOTIFY_EMAIL', 'admin@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库迁移配置
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'directory' => database_path('migrations'),
        'namespace' => 'Database\\Migrations',
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库种子配置
    |--------------------------------------------------------------------------
    */
    'seeds' => [
        'directory' => database_path('seeds'),
        'namespace' => 'Database\\Seeds',
    ],
];