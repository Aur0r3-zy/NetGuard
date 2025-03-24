# 基于人工免疫算法的分布式计算机网络攻击风险预测检测软件

## 项目简介
本项目是一个基于人工免疫算法的分布式计算机网络攻击风险预测检测软件，旨在为用户提供全面的网络安全解决方案。通过分析历史数据和实时数据，利用人工免疫算法模型进行风险评估，并能够在攻击发生前发出警报，从而降低潜在损失。

## 功能特性
- 攻击模式监测
  - 实时流量监测
  - 异常行为识别
  - 数据包分析
  - 攻击源定位
- 风险评估工具
  - 漏洞扫描
  - 威胁评估
  - 风险评分模型
  - 安全性报告生成
- 异常流量检测
  - 流量基线建立
  - 异常流量告警
  - 流量分析图表
  - 统计指标生成
- 网络安全状况
  - 安全事件追踪
  - 安全策略审核
  - 安全态势分析
  - 响应措施建议
- 数据管理
  - 入侵数据记录
  - 风险数据库管理
  - 检测日志分析
  - 历史攻击案例
- 事件响应
  - 快速响应机制
  - 处理方案生成
  - 事件追踪记录

## 技术栈
- 后端：PHP 7.4+
- 前端：Vue.js + Element UI
- 数据库：MySQL 5.7+
- 算法：人工免疫算法
- 监控：分布式计算架构

## 系统要求
- CPU：i5-11400或更高
- 内存：8GB或更高
- 硬盘：320GB或更高
- 操作系统：Windows 10/8.1/8/7

## 安装说明
1. 克隆项目到本地
```bash
git clone https://github.com/Aur0r3-zy/NetGuard/
```

2. 安装依赖
```bash
composer install
```

3. 配置数据库
- 复制 `.env.example` 为 `.env`
- 修改数据库配置信息

4. 初始化数据库
```bash
php bin/console db:migrate
```

5. 启动服务
```bash
php -S localhost:8000 -t public
```

## 目录结构

```
project/
├── src/                          # 源代码目录
│   ├── Core/                     # 核心功能模块
│   │   ├── Algorithm/           # 算法相关
│   │   │   └── ImmuneAlgorithm.php
│   │   ├── Immune/              # 免疫算法实现
│   │   │   ├── Algorithm.php    # 免疫算法核心类
│   │   │   ├── Antigen.php      # 抗原类
│   │   │   ├── Antibody.php     # 抗体类
│   │   │   └── Memory.php       # 记忆细胞类
│   │   ├── Log/                 # 日志管理
│   │   │   └── LogManager.php   # 日志管理器
│   │   └── Monitor/             # 监控模块
│   │       ├── TrafficMonitor.php    # 流量监控
│   │       ├── SecurityMonitor.php   # 安全监控
│   │       └── AttackMonitor.php     # 攻击监控
│   ├── Api/                      # API接口
│   │   └── Controllers/         # 控制器
│   │       ├── DashboardController.php
│   │       ├── IntrusionController.php
│   │       └── MonitorController.php
│   └── Models/                   # 数据模型
│       ├── Intrusion.php
│       └── Monitor.php
├── config/                       # 配置文件目录
│   ├── database.php             # 数据库配置
│   ├── traffic.php              # 流量监控配置
│   └── security.php             # 安全配置
├── public/                       # 公共访问目录
│   ├── index.php                # 入口文件
│   ├── assets/                  # 静态资源
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/                 # 上传文件目录
├── resources/                    # 资源文件
│   ├── views/                   # 视图文件
│   │   ├── dashboard/
│   │   ├── intrusion/
│   │   └── monitor/
│   └── lang/                    # 语言文件
│       └── zh/
├── storage/                      # 存储目录
│   ├── logs/                    # 日志文件
│   ├── cache/                   # 缓存文件
│   └── temp/                    # 临时文件
├── tests/                        # 测试目录
│   ├── Unit/                    # 单元测试
│   └── Integration/             # 集成测试
├── vendor/                       # 第三方依赖
├── .env                         # 环境变量
├── .gitignore                   # Git忽略文件
├── composer.json                # Composer配置
├── composer.lock                # Composer依赖锁定
├── package.json                 # NPM配置
├── README.md                    # 项目说明
└── LICENSE                      # 许可证
```

## 主要目录说明

### src/Core/
- **Algorithm/**: 包含核心算法实现
- **Immune/**: 人工免疫算法相关实现
- **Log/**: 日志管理系统
- **Monitor/**: 监控系统核心功能

### src/Api/
- **Controllers/**: API控制器，处理HTTP请求
- **Models/**: 数据模型，处理数据库交互

### config/
- 包含各种配置文件
- 数据库连接配置
- 监控参数配置
- 安全策略配置

### public/
- Web服务器入口目录
- 静态资源文件
- 上传文件存储

### resources/
- 视图模板文件
- 多语言支持文件

### storage/
- 日志文件存储
- 缓存文件存储
- 临时文件存储

### tests/
- 单元测试用例
- 集成测试用例

## 数据库表结构

```
- traffic_data           # 流量数据表
- baseline_data          # 基线数据表
- monitoring_data        # 监控数据表
- alerts                 # 告警记录表
- blacklist             # 黑名单表
- whitelist             # 白名单表
- intrusion_records     # 入侵记录表
- attack_patterns       # 攻击模式表
- risk_assessments      # 风险评估表
```

## 主要功能模块

1. **流量监控系统**
   - 实时流量监控
   - 异常检测
   - 基线分析
   - 趋势预测

2. **安全监控系统**
   - 攻击检测
   - 风险评估
   - 告警管理
   - 响应建议

3. **免疫算法系统**
   - 抗原识别
   - 抗体生成
   - 记忆细胞管理
   - 模式匹配

4. **日志管理系统**
   - 日志收集
   - 日志分析
   - 日志存储
   - 日志查询

5. **API接口系统**
   - RESTful API
   - 数据交互
   - 认证授权
   - 接口文档

## API文档
### 仪表盘接口
- GET `/api/dashboard/statistics` - 获取仪表盘统计数据

### 入侵记录接口
- GET `/api/intrusion/records` - 获取入侵记录列表
- GET `/api/intrusion/records/{id}` - 获取单条记录详情
- POST `/api/intrusion/records` - 创建新记录
- PUT `/api/intrusion/records/{id}` - 更新记录
- DELETE `/api/intrusion/records/{id}` - 删除记录
- GET `/api/intrusion/attack-types` - 获取攻击类型列表
- GET `/api/intrusion/records/export` - 导出记录为CSV

### 监控接口
- GET `/api/monitor/attack` - 获取攻击监控数据
- GET `/api/monitor/traffic` - 获取流量监控数据
- GET `/api/monitor/security` - 获取安全监控数据
- GET `/api/monitor/anomalies` - 获取异常数据

### 风险评估接口
- POST `/api/risk/scan` - 执行漏洞扫描
- GET `/api/risk/assessment` - 获取风险评估报告
- GET `/api/risk/score` - 获取风险评分

## 使用说明
1. 登录系统
   - 访问系统首页
   - 输入用户名和密码
   - 点击登录按钮

2. 使用仪表盘
   - 查看系统概览
   - 监控实时数据
   - 分析统计数据

3. 攻击模式监测
   - 监控网络流量
   - 识别异常行为
   - 分析数据包
   - 定位攻击源

4. 风险评估
   - 执行漏洞扫描
   - 评估威胁等级
   - 生成安全报告

5. 数据管理
   - 记录入侵数据
   - 管理风险数据库
   - 分析检测日志
   - 查看历史案例





