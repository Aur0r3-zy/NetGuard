# 网络攻击检测和权限管理系统

这是一个基于PHP的网络攻击检测和用户权限管理系统，提供Web界面，支持多种攻击检测和防御功能，以及细粒度的用户权限控制。

## 主要功能

- 实时检测和防御多种网络攻击
  - SQL注入攻击
  - XSS跨站脚本攻击
  - 路径遍历攻击
  - 命令注入攻击
- 用户认证和授权
  - JWT令牌认证
  - 基于角色的访问控制
  - 细粒度的权限管理
- 完整的日志记录
  - 攻击日志
  - 操作日志
  - 系统日志
- 现代化的Web界面
  - 响应式设计
  - 实时数据更新
  - 丰富的数据可视化

## 系统要求

- PHP >= 7.4
- MySQL >= 5.7
- Composer
- Node.js >= 14 (用于前端开发)

## 安装步骤

1. 克隆代码库：
```bash
git clone https://github.com/yourusername/web-attack-detection.git
cd web-attack-detection
```

2. 安装PHP依赖：
```bash
composer install
```

3. 配置数据库：
- 创建新的MySQL数据库
- 复制 `.env.example` 为 `.env`
- 修改 `.env` 中的数据库配置

4. 初始化数据库：
```bash
php src/database/init.sql
```

5. 启动开发服务器：
```bash
php -S localhost:8000 -t public
```

现在可以访问 http://localhost:8000 来使用系统。

## 默认账户

系统会自动创建一个默认的管理员账户：

- 用户名：admin
- 密码：admin123

**请在首次登录后立即修改密码！**

## API文档

### 认证相关

#### 登录
```
POST /api/auth/login
Content-Type: application/json

{
    "username": "your_username",
    "password": "your_password"
}
```

#### 注销
```
POST /api/auth/logout
Authorization: Bearer your_token
```

### 用户管理

#### 获取用户列表
```
GET /api/users
Authorization: Bearer your_token
```

#### 创建用户
```
POST /api/users
Authorization: Bearer your_token
Content-Type: application/json

{
    "username": "newuser",
    "password": "password123",
    "email": "user@example.com",
    "role": "user"
}
```

### 权限管理

#### 获取权限列表
```
GET /api/permissions
Authorization: Bearer your_token
```

#### 分配权限
```
POST /api/users/{user_id}/permissions
Authorization: Bearer your_token
Content-Type: application/json

{
    "permissions": ["permission1", "permission2"]
}
```

### 日志查询

#### 获取攻击日志
```
GET /api/attack-logs
Authorization: Bearer your_token
```

#### 获取操作日志
```
GET /api/activity-logs
Authorization: Bearer your_token
```

## 安全配置

1. 确保 `.env` 文件中包含足够强度的密钥：
```
JWT_SECRET=your_very_long_random_secret_key
```

2. 配置适当的文件权限：
```bash
chmod 755 public/
chmod 644 public/*
chmod 755 src/
chmod 644 src/*
```

3. 确保日志目录可写：
```bash
chmod 755 logs/
```

## 开发指南

### 添加新的攻击检测规则

1. 在 `src/Middleware/SecurityMiddleware.php` 中的 `$attackPatterns` 数组添加新的检测模式
2. 在 `determineAttackSeverity()` 方法中添加相应的严重程度判断逻辑

### 添加新的权限

1. 在数据库的 `permissions` 表中添加新权限
2. 在 `src/Middleware/PermissionMiddleware.php` 中的 `$routePermissions` 数组更新路由权限配置

## 贡献指南

1. Fork 本项目
2. 创建您的特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交您的修改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建一个 Pull Request

## 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详细信息。 