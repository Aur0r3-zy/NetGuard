# NetGuard

## 项目简介
本项目是一个基于人工免疫算法的分布式计算机网络攻击风险预测检测软件，旨在为用户提供全面的网络安全解决方案。通过分析历史数据和实时数据，利用人工免疫算法模型进行风险评估，并能够针对网络攻击发生发出警报，从而降低潜在损失。

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
- 操作系统：Windows、Linux

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
│  .env.example            # 环境变量示例配置文件，供用户参考填写
│  .gitignore              # Git忽略文件配置
│  composer.json           # PHP依赖管理器 Composer 的配置文件
│  config.json             # 应用程序的主要配置文件
│  phpunit.xml             # PHPUnit 测试配置文件
│  README.md               # 项目简介和使用说明
│
├─config                   # 配置文件目录
│      database.php        # 数据库连接和设置配置
│
├─public                   # 应用的公共文件，前端展示和API入口
│  │  dashboard.html       # 仪表板的静态HTML页面
│  │  index.html           # 应用程序首页的静态HTML页面
│  │  index.php            # 应用程序入口文件，用于加载初始配置和处理请求
│  ├─api                   # API相关文件目录
│  │      index.php        # API的入口文件，用于处理API请求
│  ├─components            # 前端可复用的UI组件
│  │      AttackMonitor.js # 攻击监控前端组件
│  │      IntrusionRecords.js # 入侵记录展示组件
│  │      RiskAssessment.js # 风险评估前端组件
│  │      SecurityStatus.js # 安全状态前端组件
│  │      TrafficMonitor.js # 流量监控前端组件
│  └─js                    # JavaScript文件目录
│          api.js          # 与后端API交互的JS工具类
│  |─views
│          404.php         # 404错误页面
│         500.php         # 500错误页面
│          alerts.php      # 告警信息页面
│          dashboard.php   # 仪表板页面
│          layout.php      # 页面布局模板，包含头部和脚部
│          monitor.php     # 监控页面，显示网络攻击的实时监控数据
│          settings.php    # 系统设置页面
│
├─src                      # 核心代码目录
│  │  index.php            # 应用的主要引导文件，加载必要的类和配置
│  │
│  ├─Api                   # API功能相关的文件
│  │  │  Request.php       # 处理API请求的类
│  │  │  Router.php        # 路由处理类，管理不同请求的转发
│  │  │  routes.php        # 定义API路由的配置文件
│  │  │
│  │  ├─Controller         # 控制器类，管理API请求处理
│  │  │      DashboardController.php  # 仪表板API控制器
│  │  │      IntrusionController.php  # 入侵检测控制器
│  │  │      MonitorController.php    # 监控数据的API控制器
│  │  │      RiskController.php       # 风险评估控制器
│  │  │
│  │  ├─Exception          # 异常处理类
│  │  │      UnauthorizedException.php # 未授权异常类
│  │  │
│  │  └─Middleware         # 中间件处理目录
│  │          AuthMiddleware.php      # 认证中间件，验证用户身份
│  │          CorsMiddleware.php      # 跨域请求处理中间件
│  │          MiddlewareInterface.php # 中间件接口类
│  │
│  ├─Config                # 配置加载类
│  │      ConfigLoader.php  # 加载和管理配置文件的类
│  │
│  ├─Controllers           # 控制器文件
│  │      ApiController.php # API控制器，处理与API相关的请求
│  │
│  ├─Core                  # 核心功能模块
│  │  ├─Auth               # 认证相关
│  │  │      Authenticator.php # 处理用户认证和授权
│  │  │
│  │  ├─Config             # 系统配置模块
│  │  │      SystemSettings.php # 系统设置管理类
│  │  │
│  │  ├─Data               # 数据管理模块
│  │  │      IntrusionComments.php  # 入侵事件的评论数据类
│  │  │      IntrusionRecord.php    # 入侵记录数据类
│  │  │      IntrusionStatistics.php# 入侵统计数据类
│  │  │      IntrusionTags.php      # 入侵标签数据类
│  │  │
│  │  ├─Immune             # 人工免疫算法相关模块
│  │  │      Algorithm.php  # 免疫算法主逻辑
│  │  │      Antibody.php   # 抗体数据结构类
│  │  │      Antigen.php    # 抗原数据结构类
│  │  │      Memory.php     # 免疫记忆算法类
│  │  │
│  │  ├─Log                # 日志处理模块
│  │  │      LogManager.php # 管理和记录系统日志
│  │  │
│  │  ├─Monitor            # 监控功能模块
│  │  │      AttackMonitor.php  # 攻击监控逻辑
│  │  │      SecurityMonitor.php# 安全监控逻辑
│  │  │      TrafficMonitor.php # 流量监控逻辑
│  │  │
│  │  ├─Network            # 网络数据处理模块
│  │  │      PacketProcessor.php  # 数据包处理逻辑
│  │  │      TrafficAnalyzer.php  # 网络流量分析逻辑
│  │  │
│  │  ├─Risk               # 风险评估模块
│  │  │      RiskAssessor.php # 处理风险评估逻辑
│  │  │
│  │  └─Session            # 会话管理模块
│  │          Session.php   # 管理用户会话的类
│  │
│  ├─Data                  # 数据处理模块
│  │      FeatureExtractor.php # 特征提取逻辑
│  │      Normalizer.php       # 数据标准化处理
│  │      Preprocessor.php     # 数据预处理逻辑
│  │
│  ├─Database              # 数据库操作相关模块
│  │  │  Database.php       # 数据库连接和操作类
│  │  │  init.php           # 初始化数据库配置
│  │  │
│  │  ├─Migrations         # 数据库迁移模块
│  │  │      create_tables.php # 创建数据库表的迁移文件
│  │  │
│  │  └─Seeds              # 数据填充模块
│  │          init_data.php # 初始化数据库数据
│  │
│  ├─routes                # 路由配置
│  │      api.php           # 定义API路由规则
│  │
│  └─Utils                 # 工具类
│          Database.php     # 数据库工具类
│          Logger.php       # 日志工具类
│
└─tests                    # 测试文件目录
    ├─Core/Immune          # 免疫算法相关测试
    │      AlgorithmTest.php # 测试免疫算法的功能
    │
    ├─Performance          # 性能测试相关
    │      PerformanceTest.php # 测试系统性能的类
    │
    └─Security             # 安全功能相关测试
           SecurityTest.php  # 测试系统的安全功能

```
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





