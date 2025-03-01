const Dashboard = {
    template: `
        <div class="dashboard">
            <el-row :gutter="20">
                <!-- 统计卡片 -->
                <el-col :span="6" v-for="stat in stats" :key="stat.title">
                    <el-card class="dashboard-card" :body-style="{ padding: '20px' }">
                        <div class="stat-icon" :class="stat.type">
                            <i :class="stat.icon"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">{{ stat.title }}</div>
                            <div class="stat-number">{{ stat.value }}</div>
                            <div class="stat-desc">{{ stat.description }}</div>
                        </div>
                    </el-card>
                </el-col>
            </el-row>

            <!-- 攻击趋势图表 -->
            <el-row :gutter="20" style="margin-top: 20px;">
                <el-col :span="16">
                    <el-card>
                        <template #header>
                            <div class="card-header">
                                <span>攻击趋势分析</span>
                                <el-radio-group v-model="timeRange" size="small">
                                    <el-radio-button label="day">今日</el-radio-button>
                                    <el-radio-button label="week">本周</el-radio-button>
                                    <el-radio-button label="month">本月</el-radio-button>
                                </el-radio-group>
                            </div>
                        </template>
                        <div ref="attackChart" style="height: 300px;"></div>
                    </el-card>
                </el-col>
                <el-col :span="8">
                    <el-card>
                        <template #header>
                            <div class="card-header">
                                <span>攻击类型分布</span>
                            </div>
                        </template>
                        <div ref="attackTypeChart" style="height: 300px;"></div>
                    </el-card>
                </el-col>
            </el-row>

            <!-- 最近攻击记录 -->
            <el-row style="margin-top: 20px;">
                <el-col :span="24">
                    <el-card>
                        <template #header>
                            <div class="card-header">
                                <span>最近攻击记录</span>
                                <el-button type="text" @click="viewAllLogs">查看全部</el-button>
                            </div>
                        </template>
                        <el-table :data="recentAttacks" style="width: 100%">
                            <el-table-column prop="created_at" label="时间" width="180">
                                <template #default="scope">
                                    {{ formatDate(scope.row.created_at) }}
                                </template>
                            </el-table-column>
                            <el-table-column prop="attack_type" label="攻击类型" width="150" />
                            <el-table-column prop="ip_address" label="IP地址" width="150" />
                            <el-table-column prop="severity" label="严重程度" width="100">
                                <template #default="scope">
                                    <el-tag :type="getSeverityType(scope.row.severity)">
                                        {{ scope.row.severity }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="request_uri" label="请求路径" />
                            <el-table-column prop="status" label="状态" width="100">
                                <template #default="scope">
                                    <el-tag :type="getStatusType(scope.row.status)">
                                        {{ scope.row.status }}
                                    </el-tag>
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </el-col>
            </el-row>
        </div>
    `,

    setup() {
        const stats = ref([
            {
                title: '今日攻击',
                value: 0,
                description: '较昨日 +5%',
                icon: 'el-icon-warning',
                type: 'danger'
            },
            {
                title: '活跃用户',
                value: 0,
                description: '在线用户数',
                icon: 'el-icon-user',
                type: 'primary'
            },
            {
                title: '系统负载',
                value: '45%',
                description: '服务器状态正常',
                icon: 'el-icon-cpu',
                type: 'success'
            },
            {
                title: '安全评分',
                value: 95,
                description: '系统安全状态良好',
                icon: 'el-icon-shield',
                type: 'warning'
            }
        ]);

        const timeRange = ref('day');
        const recentAttacks = ref([]);
        const attackChart = ref(null);
        const attackTypeChart = ref(null);

        // 初始化图表
        const initCharts = () => {
            // 攻击趋势图表
            const attackChartInstance = echarts.init(attackChart.value);
            attackChartInstance.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                xAxis: {
                    type: 'category',
                    data: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00']
                },
                yAxis: {
                    type: 'value'
                },
                series: [{
                    data: [5, 8, 3, 12, 6, 15, 7, 4],
                    type: 'line',
                    smooth: true,
                    areaStyle: {}
                }]
            });

            // 攻击类型分布图表
            const attackTypeChartInstance = echarts.init(attackTypeChart.value);
            attackTypeChartInstance.setOption({
                tooltip: {
                    trigger: 'item'
                },
                legend: {
                    orient: 'vertical',
                    left: 'left'
                },
                series: [{
                    type: 'pie',
                    radius: '50%',
                    data: [
                        { value: 35, name: 'SQL注入' },
                        { value: 25, name: 'XSS攻击' },
                        { value: 20, name: '路径遍历' },
                        { value: 15, name: '命令注入' },
                        { value: 5, name: '其他' }
                    ],
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }]
            });
        };

        // 获取统计数据
        const fetchStats = async () => {
            try {
                const response = await axios.get('/api/dashboard/stats');
                // 更新统计数据
                stats.value = response.data;
            } catch (error) {
                ElMessage.error('获取统计数据失败');
            }
        };

        // 获取最近攻击记录
        const fetchRecentAttacks = async () => {
            try {
                const response = await axios.get('/api/attack-logs?limit=5');
                recentAttacks.value = response.data.data;
            } catch (error) {
                ElMessage.error('获取攻击记录失败');
            }
        };

        // 格式化日期
        const formatDate = (date) => {
            return new Date(date).toLocaleString();
        };

        // 获取严重程度对应的标签类型
        const getSeverityType = (severity) => {
            const types = {
                'low': 'info',
                'medium': 'warning',
                'high': 'danger',
                'critical': 'danger'
            };
            return types[severity] || 'info';
        };

        // 获取状态对应的标签类型
        const getStatusType = (status) => {
            const types = {
                'detected': 'warning',
                'blocked': 'success',
                'investigating': 'info'
            };
            return types[status] || 'info';
        };

        // 查看所有日志
        const viewAllLogs = () => {
            window.location.href = '/#/attack-logs';
        };

        // 监听时间范围变化
        watch(timeRange, (newValue) => {
            // 更新图表数据
            fetchChartData(newValue);
        });

        // 页面加载时初始化
        onMounted(() => {
            initCharts();
            fetchStats();
            fetchRecentAttacks();
        });

        return {
            stats,
            timeRange,
            recentAttacks,
            attackChart,
            attackTypeChart,
            formatDate,
            getSeverityType,
            getStatusType,
            viewAllLogs
        };
    }
}; 