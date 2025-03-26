/**
 * API服务类 - 处理所有与后端的通信
 */
class ApiService {
    constructor() {
        this.baseUrl = '/api';
        this.token = localStorage.getItem('token');
        this.retryCount = 3;
        this.retryDelay = 1000;
        this.pendingRequests = new Map();
    }

    /**
     * 设置认证token
     * @param {string} token - JWT token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
    }

    /**
     * 移除认证token
     */
    removeToken() {
        this.token = null;
        localStorage.removeItem('token');
    }

    /**
     * 通用请求方法
     * @param {string} endpoint - API端点
     * @param {Object} options - 请求选项
     * @returns {Promise} 请求结果
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const controller = new AbortController();
        const signal = controller.signal;

        // 存储请求控制器以便后续可以取消
        this.pendingRequests.set(url, controller);

        try {
            const response = await this._makeRequest(url, {
                ...options,
                headers,
                signal
            });

            this.pendingRequests.delete(url);
            return response;
        } catch (error) {
            this.pendingRequests.delete(url);
            throw this._handleError(error);
        }
    }

    /**
     * 执行请求，包含重试机制
     * @private
     */
    async _makeRequest(url, options, retryCount = 0) {
        try {
            const response = await fetch(url, options);

            if (!response.ok) {
                if (response.status === 401) {
                    this.removeToken();
                    window.location.href = '/index.html';
                    throw new Error('未授权访问');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            if (retryCount < this.retryCount) {
                await new Promise(resolve => setTimeout(resolve, this.retryDelay));
                return this._makeRequest(url, options, retryCount + 1);
            }
            throw error;
        }
    }

    /**
     * 处理错误
     * @private
     */
    _handleError(error) {
        console.error('API请求错误:', error);
        if (error.name === 'AbortError') {
            return new Error('请求已取消');
        }
        return error;
    }

    /**
     * 取消所有待处理的请求
     */
    cancelAllRequests() {
        this.pendingRequests.forEach(controller => controller.abort());
        this.pendingRequests.clear();
    }

    /**
     * 取消特定请求
     * @param {string} endpoint - API端点
     */
    cancelRequest(endpoint) {
        const url = `${this.baseUrl}${endpoint}`;
        const controller = this.pendingRequests.get(url);
        if (controller) {
            controller.abort();
            this.pendingRequests.delete(url);
        }
    }

    // 认证相关API
    auth = {
        login: (credentials) => {
            return ApiService.prototype.request('/auth/login', {
                method: 'POST',
                body: JSON.stringify(credentials)
            });
        },
        logout: () => {
            return ApiService.prototype.request('/auth/logout', {
                method: 'POST'
            });
        },
        refreshToken: () => {
            return ApiService.prototype.request('/auth/refresh', {
                method: 'POST'
            });
        }
    };

    // 仪表板相关API
    dashboard = {
        getStatistics: () => {
            return ApiService.prototype.request('/dashboard/statistics');
        },
        getAttackTypes: () => {
            return ApiService.prototype.request('/dashboard/attack-types');
        },
        getRiskTrend: (params) => {
            return ApiService.prototype.request('/dashboard/risk-trend', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        },
        getRecentActivities: () => {
            return ApiService.prototype.request('/dashboard/recent-activities');
        }
    };

    // 攻击监测相关API
    attackMonitor = {
        getAttackList: (params) => {
            return ApiService.prototype.request('/attack-monitor/list', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        },
        getAttackDetails: (id) => {
            return ApiService.prototype.request(`/attack-monitor/details/${id}`);
        },
        updateAttackStatus: (id, status) => {
            return ApiService.prototype.request(`/attack-monitor/status/${id}`, {
                method: 'PUT',
                body: JSON.stringify({ status })
            });
        }
    };

    // 风险评估相关API
    riskAssessment = {
        getRiskScore: () => {
            return ApiService.prototype.request('/risk-assessment/score');
        },
        getVulnerabilities: () => {
            return ApiService.prototype.request('/risk-assessment/vulnerabilities');
        },
        getRiskHistory: (params) => {
            return ApiService.prototype.request('/risk-assessment/history', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        }
    };

    // 流量分析相关API
    trafficMonitor = {
        getTrafficStats: () => {
            return ApiService.prototype.request('/traffic-monitor/stats');
        },
        getAnomalies: (params) => {
            return ApiService.prototype.request('/traffic-monitor/anomalies', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        },
        getTrafficHistory: (params) => {
            return ApiService.prototype.request('/traffic-monitor/history', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        }
    };

    // 日志相关API
    logs = {
        getLogList: (params) => {
            return ApiService.prototype.request('/logs/list', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(params)
            });
        },
        getLogDetails: (id) => {
            return ApiService.prototype.request(`/logs/details/${id}`);
        },
        exportLogs: (params) => {
            return ApiService.prototype.request('/logs/export', {
                method: 'POST',
                body: JSON.stringify(params)
            });
        }
    };

    // 系统设置相关API
    settings = {
        getSettings: () => {
            return ApiService.prototype.request('/settings');
        },
        updateSettings: (settings) => {
            return ApiService.prototype.request('/settings', {
                method: 'PUT',
                body: JSON.stringify(settings)
            });
        },
        getSystemStatus: () => {
            return ApiService.prototype.request('/settings/status');
        }
    };
}

// 创建全局API服务实例
window.api = new ApiService();

// 工具函数
const utils = {
    /**
     * 格式化字节数
     * @param {number} bytes - 字节数
     * @returns {string} 格式化后的字符串
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * 更新攻击模式图表
     * @param {Array} types - 攻击类型数据
     */
    updateAttackPatternsChart(types) {
        const ctx = document.getElementById('attack-patterns-chart')?.getContext('2d');
        if (!ctx) return;

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
    },

    /**
     * 更新流量趋势图表
     * @param {Array} trend - 趋势数据
     */
    updateTrafficTrendChart(trend) {
        const ctx = document.getElementById('traffic-trend-chart')?.getContext('2d');
        if (!ctx) return;

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
};

// 导出工具函数
window.utils = utils;

// 实时更新函数
function updateDashboard() {
    // 更新流量统计
    ApiService.prototype.trafficMonitor.getTrafficStats()
        .then(stats => {
            document.getElementById('total-packets').textContent = stats.total_packets;
            document.getElementById('total-bytes').textContent = utils.formatBytes(stats.total_bytes);
            document.getElementById('unique-sources').textContent = stats.unique_sources;
            document.getElementById('unique-destinations').textContent = stats.unique_destinations;
        })
        .catch(error => console.error('更新流量统计失败:', error));
    
    // 更新告警统计
    ApiService.prototype.dashboard.getStatistics()
        .then(stats => {
            document.getElementById('total-alerts').textContent = stats.total_alerts;
            document.getElementById('critical-alerts').textContent = stats.critical_alerts;
            document.getElementById('warning-alerts').textContent = stats.warning_alerts;
            document.getElementById('open-alerts').textContent = stats.open_alerts;
        })
        .catch(error => console.error('更新告警统计失败:', error));
    
    // 更新攻击模式图表
    ApiService.prototype.dashboard.getAttackTypes()
        .then(types => {
            utils.updateAttackPatternsChart(types);
        })
        .catch(error => console.error('更新攻击模式图表失败:', error));
    
    // 更新流量趋势图表
    ApiService.prototype.trafficMonitor.getTrafficHistory()
        .then(trend => {
            utils.updateTrafficTrendChart(trend);
        })
        .catch(error => console.error('更新流量趋势图表失败:', error));
}

// 页面加载完成后开始实时更新
document.addEventListener('DOMContentLoaded', () => {
    // 初始更新
    updateDashboard();
    
    // 每30秒更新一次
    setInterval(updateDashboard, 30000);
}); 