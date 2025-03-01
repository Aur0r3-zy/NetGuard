# 网络攻击检测与权限管理系统

## 项目简介
本系统是一个基于PHP的网络攻击检测与权限管理系统，提供实时攻击检测、用户权限管理、日志记录等功能。系统采用前后端分离架构，前端使用Vue.js和Element Plus，后端使用PHP，数据库使用MySQL。

## 主要功能
- 用户认证与授权
  - 用户登录/登出
  - 基于角色的访问控制（RBAC）
  - 细粒度的权限管理

- 攻击检测
  - SQL注入检测
  - XSS攻击检测
  - 路径遍历检测
  - 命令注入检测
  - 实时告警

- 系统监控
  - 系统负载监控
  - 用户活动监控
  - 攻击趋势分析
  - 安全评分

- 日志管理
  - 攻击日志记录
  - 用户操作日志
  - 系统事件日志

## 技术栈
- 前端
  - Vue.js 3
  - Element Plus
  - ECharts
  - Axios

- 后端
  - PHP 8.0+
  - Slim Framework
  - Doctrine DBAL
  - Monolog

- 数据库
  - MySQL 8.0+

## 安装步骤

1. 克隆项目
```bash
git clone [项目地址]
cd web_attack_test
```

2. 安装PHP依赖
```bash
composer install
```

3. 配置环境变量
```bash
cp .env.example .env
# 编辑.env文件，配置数据库连接等信息
```

4. 初始化数据库
```bash
# 导入数据库结构
mysql -u your_username -p your_database < database/schema.sql
# 导入初始数据
mysql -u your_username -p your_database < database/seed.sql
```

5. 配置Web服务器
```apache
# Apache配置示例
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/web_attack_test/public
    
    <Directory /path/to/web_attack_test/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. 设置目录权限
```bash
chmod -R 755 storage/logs
chmod -R 755 storage/cache
```

## 系统要求
- PHP >= 8.0
- MySQL >= 8.0
- Apache/Nginx
- Composer
- mod_rewrite 模块（Apache）

## 安全配置
1. 确保 `.env` 文件不被公开访问
2. 配置适当的文件权限
3. 启用HTTPS
4. 定期更新依赖包
5. 配置防火墙规则

## API文档
API文档位于 `docs/api.md`，包含所有接口的详细说明。

## 开发指南
1. 代码规范遵循PSR-12
2. 提交代码前运行测试
3. 保持日志记录的完整性
4. 遵循安全编码最佳实践

## 目录结构
```
web_attack_test/
├── config/             # 配置文件
├── database/           # 数据库文件
├── docs/              # 文档
├── public/            # 公共文件
│   ├── css/          # 样式文件
│   ├── js/           # JavaScript文件
│   └── index.php     # 入口文件
├── src/               # 源代码
│   ├── Controllers/  # 控制器
│   ├── Services/     # 服务层
│   ├── Middleware/   # 中间件
│   └── Models/       # 模型
├── storage/           # 存储目录
│   ├── logs/        # 日志文件
│   └── cache/       # 缓存文件
├── tests/            # 测试文件
├── vendor/           # Composer依赖
├── .env.example      # 环境变量示例
├── composer.json     # Composer配置
└── README.md         # 项目说明
```

## 常见问题
1. 权限问题：确保storage目录可写
2. 数据库连接：检查.env配置
3. URL重写：确保mod_rewrite已启用

## 更新日志
### v1.0.0 (2024-03-xx)
- 初始版本发布
- 基础用户管理功能
- 攻击检测功能
- 权限管理系统
- 日志记录功能

## 贡献指南
1. Fork 项目
2. 创建特性分支
3. 提交更改
4. 推送到分支
5. 创建Pull Request

## 许可证
MIT License 