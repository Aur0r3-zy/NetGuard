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
git clone [项目地址]
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
├── public/                 # 公共访问目录
│   ├── api/               # API入口
│   ├── components/        # Vue组件
│   ├── css/              # 样式文件
│   ├── js/               # JavaScript文件
│   └── index.html        # 入口页面
├── src/                   # 源代码目录
│   ├── Api/              # API相关代码
│   │   ├── Controller/   # 控制器
│   │   └── routes.php    # 路由配置
│   ├── Core/             # 核心功能
│   │   ├── Algorithm/    # 算法实现
│   │   ├── Data/         # 数据处理
│   │   └── Monitor/      # 监控模块
│   └── Config/           # 配置文件
├── tests/                 # 测试文件
├── vendor/                # 依赖包
├── .env                   # 环境配置
├── composer.json          # 项目配置
└── README.md             # 项目说明
```

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

## 版本信息
- 当前版本：V1.0
- 发布日期：2024-03-21
- 更新日志：详见CHANGELOG.md

## 许可证
本项目采用 MIT 许可证，详见 LICENSE 文件。



