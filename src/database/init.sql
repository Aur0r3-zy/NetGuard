CREATE DATABASE IF NOT EXISTS security_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE security_system;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 权限表
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 用户权限关联表
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 攻击日志表
CREATE TABLE IF NOT EXISTS attack_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attack_type VARCHAR(50) NOT NULL,
    request_method VARCHAR(10) NOT NULL,
    request_uri TEXT NOT NULL,
    request_body TEXT,
    user_agent TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('detected', 'blocked', 'investigating') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 系统操作日志表
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
); 