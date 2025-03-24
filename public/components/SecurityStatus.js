Vue.component('SecurityStatus', {
    template: `
        <div class="security-status">
            <el-card>
                <div slot="header">
                    <span>网络安全状况</span>
                    <el-button style="float: right" type="primary" size="small" @click="refreshData">刷新数据</el-button>
                </div>

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-card class="status-card">
                            <div slot="header">
                                <span>安全事件追踪</span>
                            </div>
                            <el-table :data="securityEvents" style="width: 100%">
                                <el-table-column prop="time" label="时间" width="180"></el-table-column>
                                <el-table-column prop="type" label="事件类型" width="120"></el-table-column>
                                <el-table-column prop="description" label="描述"></el-table-column>
                                <el-table-column prop="level" label="级别" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getEventLevelType(scope.row.level)">
                                            {{ scope.row.level }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getEventStatusType(scope.row.status)">
                                            {{ scope.row.status }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="status-card">
                            <div slot="header">
                                <span>安全策略审核</span>
                            </div>
                            <el-table :data="securityPolicies" style="width: 100%">
                                <el-table-column prop="name" label="策略名称"></el-table-column>
                                <el-table-column prop="type" label="类型" width="120"></el-table-column>
                                <el-table-column prop="lastReview" label="最后审核" width="180"></el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getPolicyStatusType(scope.row.status)">
                                            {{ scope.row.status }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-card>
                    </el-col>
                </el-row>

                <el-row :gutter="20" style="margin-top: 20px">
                    <el-col :span="12">
                        <el-card class="status-card">
                            <div slot="header">
                                <span>安全态势分析</span>
                            </div>
                            <div id="securityTrendChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="status-card">
                            <div slot="header">
                                <span>响应措施建议</span>
                            </div>
                            <el-table :data="recommendations" style="width: 100%">
                                <el-table-column prop="category" label="类别"></el-table-column>
                                <el-table-column prop="description" label="建议内容"></el-table-column>
                                <el-table-column prop="priority" label="优先级" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getPriorityType(scope.row.priority)">
                                            {{ scope.row.priority }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-card>
                    </el-col>
                </el-row>
            </el-card>
        </div>
    `,
    data() {
        return {
            securityEvents: [],
            securityPolicies: [],
            recommendations: [],
            securityTrendChart: null
        }
    },
    methods: {
        async fetchData() {
            try {
                const response = await axios.get('/api/monitor/security');
                if (response.data.code === 200) {
                    const data = response.data.data;
                    this.securityEvents = data.events;
                    this.securityPolicies = data.policies;
                    this.recommendations = data.recommendations;
                    this.updateChart(data.trend);
                }
            } catch (error) {
                this.$message.error('获取安全状态数据失败');
            }
        },
        updateChart(trendData) {
            this.securityTrendChart.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['安全事件', '威胁等级', '防护效果']
                },
                xAxis: {
                    type: 'category',
                    data: trendData.times
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: '安全事件',
                        type: 'line',
                        data: trendData.events,
                        smooth: true
                    },
                    {
                        name: '威胁等级',
                        type: 'line',
                        data: trendData.threats,
                        smooth: true
                    },
                    {
                        name: '防护效果',
                        type: 'line',
                        data: trendData.protection,
                        smooth: true
                    }
                ]
            });
        },
        initChart() {
            this.securityTrendChart = echarts.init(document.getElementById('securityTrendChart'));
        },
        getEventLevelType(level) {
            const types = {
                '严重': 'danger',
                '警告': 'warning',
                '提示': 'info'
            };
            return types[level] || 'info';
        },
        getEventStatusType(status) {
            const types = {
                '已处理': 'success',
                '处理中': 'warning',
                '未处理': 'danger'
            };
            return types[status] || 'info';
        },
        getPolicyStatusType(status) {
            const types = {
                '有效': 'success',
                '待审核': 'warning',
                '已过期': 'danger'
            };
            return types[status] || 'info';
        },
        getPriorityType(priority) {
            const types = {
                '高': 'danger',
                '中': 'warning',
                '低': 'info'
            };
            return types[priority] || 'info';
        },
        refreshData() {
            this.fetchData();
        }
    },
    mounted() {
        this.initChart();
        this.fetchData();
        // 定时刷新数据
        setInterval(this.refreshData, 30000);
        
        window.addEventListener('resize', () => {
            this.securityTrendChart && this.securityTrendChart.resize();
        });
    }
}); 