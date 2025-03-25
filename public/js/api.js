// API服务类
const ApiService = {
    // 基础URL
    baseURL: '/api',

    // 请求拦截器
    requestInterceptor(config) {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },

    // 响应拦截器
    responseInterceptor(response) {
        if (response.data.code === 401) {
            // token过期或无效
            localStorage.removeItem('token');
            window.location.href = '/index.html';
        }
        return response;
    },

    // 初始化axios配置
    init() {
        axios.interceptors.request.use(this.requestInterceptor);
        axios.interceptors.response.use(this.responseInterceptor);
    },

    // 仪表盘相关API
    dashboard: {
        // 获取统计数据
        getStatistics() {
            return axios.get(`${ApiService.baseURL}/dashboard/statistics`);
        },
        
        // 获取攻击类型分布
        getAttackTypes() {
            return axios.get(`${ApiService.baseURL}/dashboard/attack-types`);
        },
        
        // 获取风险趋势
        getRiskTrend(params) {
            return axios.get(`${ApiService.baseURL}/dashboard/risk-trend`, { params });
        }
    },

    // 攻击监控相关API
    attackMonitor: {
        // 获取实时攻击数据
        getRealTimeData() {
            return axios.get(`${ApiService.baseURL}/attack-monitor/realtime`);
        },
        
        // 获取攻击详情
        getAttackDetails(id) {
            return axios.get(`${ApiService.baseURL}/attack-monitor/details/${id}`);
        },
        
        // 更新攻击状态
        updateStatus(id, status) {
            return axios.put(`${ApiService.baseURL}/attack-monitor/status/${id}`, { status });
        }
    },

    // 风险评估相关API
    riskAssessment: {
        // 获取风险评估结果
        getAssessment() {
            return axios.get(`${ApiService.baseURL}/risk-assessment/result`);
        },
        
        // 获取风险详情
        getRiskDetails(id) {
            return axios.get(`${ApiService.baseURL}/risk-assessment/details/${id}`);
        },
        
        // 更新风险等级
        updateRiskLevel(id, level) {
            return axios.put(`${ApiService.baseURL}/risk-assessment/level/${id}`, { level });
        }
    },

    // 流量监控相关API
    trafficMonitor: {
        // 获取实时流量数据
        getRealTimeTraffic() {
            return axios.get(`${ApiService.baseURL}/traffic-monitor/realtime`);
        },
        
        // 获取流量统计
        getTrafficStats(params) {
            return axios.get(`${ApiService.baseURL}/traffic-monitor/stats`, { params });
        },
        
        // 获取异常流量
        getAnomalyTraffic() {
            return axios.get(`${ApiService.baseURL}/traffic-monitor/anomaly`);
        }
    },

    // 系统设置相关API
    settings: {
        // 获取系统配置
        getConfig() {
            return axios.get(`${ApiService.baseURL}/settings/config`);
        },
        
        // 更新系统配置
        updateConfig(config) {
            return axios.put(`${ApiService.baseURL}/settings/config`, config);
        },
        
        // 获取告警规则
        getAlertRules() {
            return axios.get(`${ApiService.baseURL}/settings/alert-rules`);
        },
        
        // 更新告警规则
        updateAlertRules(rules) {
            return axios.put(`${ApiService.baseURL}/settings/alert-rules`, rules);
        }
    },

    // 日志相关API
    logs: {
        // 获取系统日志
        getSystemLogs(params) {
            return axios.get(`${ApiService.baseURL}/logs/system`, { params });
        },
        
        // 获取安全日志
        getSecurityLogs(params) {
            return axios.get(`${ApiService.baseURL}/logs/security`, { params });
        },
        
        // 导出日志
        exportLogs(type, params) {
            return axios.get(`${ApiService.baseURL}/logs/export/${type}`, {
                params,
                responseType: 'blob'
            });
        }
    }
};

// 初始化API服务
ApiService.init();

// 实时更新函数
function updateDashboard() {
    // 更新流量统计
    ApiService.trafficMonitor.getTrafficStats()
        .then(stats => {
            document.getElementById('total-packets').textContent = stats.total_packets;
            document.getElementById('total-bytes').textContent = formatBytes(stats.total_bytes);
            document.getElementById('unique-sources').textContent = stats.unique_sources;
            document.getElementById('unique-destinations').textContent = stats.unique_destinations;
        })
        .catch(error => console.error('更新流量统计失败:', error));
    
    // 更新告警统计
    ApiService.dashboard.getStatistics()
        .then(stats => {
            document.getElementById('total-alerts').textContent = stats.total_alerts;
            document.getElementById('critical-alerts').textContent = stats.critical_alerts;
            document.getElementById('warning-alerts').textContent = stats.warning_alerts;
            document.getElementById('open-alerts').textContent = stats.open_alerts;
        })
        .catch(error => console.error('更新告警统计失败:', error));
    
    // 更新攻击模式图表
    ApiService.dashboard.getAttackTypes()
        .then(types => {
            updateAttackPatternsChart(types);
        })
        .catch(error => console.error('更新攻击模式图表失败:', error));
    
    // 更新流量趋势图表
    ApiService.trafficMonitor.getRealTimeTraffic()
        .then(trend => {
            updateTrafficTrendChart(trend);
        })
        .catch(error => console.error('更新流量趋势图表失败:', error));
}

// 工具函数
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 图表更新函数
function updateAttackPatternsChart(types) {
    const ctx = document.getElementById('attack-patterns-chart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: types.map(t => t.attack_type),
            datasets: [{
                label: '攻击次数',
                data: types.map(t => t.count),
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateTrafficTrendChart(trend) {
    const ctx = document.getElementById('traffic-trend-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trend.map(t => t.hour),
            datasets: [{
                label: '数据包数量',
                data: trend.map(t => t.packet_count),
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }, {
                label: '总流量',
                data: trend.map(t => t.total_bytes),
                borderColor: 'rgba(153, 102, 255, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// 页面加载完成后开始实时更新
document.addEventListener('DOMContentLoaded', () => {
    // 初始更新
    updateDashboard();
    
    // 每30秒更新一次
    setInterval(updateDashboard, 30000);
}); 