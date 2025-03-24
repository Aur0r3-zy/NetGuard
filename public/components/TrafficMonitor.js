Vue.component('TrafficMonitor', {
    template: `
        <div class="traffic-monitor">
            <el-card>
                <div slot="header">
                    <span>异常流量检测</span>
                    <el-button style="float: right" type="primary" size="small" @click="refreshData">刷新数据</el-button>
                </div>

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>流量基线</span>
                            </div>
                            <div id="baselineChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>异常流量告警</span>
                            </div>
                            <el-table :data="alerts" style="width: 100%">
                                <el-table-column prop="time" label="时间" width="180"></el-table-column>
                                <el-table-column prop="type" label="类型" width="120"></el-table-column>
                                <el-table-column prop="source" label="来源"></el-table-column>
                                <el-table-column prop="severity" label="严重程度" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getSeverityType(scope.row.severity)">
                                            {{ scope.row.severity }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="scope.row.status === '已处理' ? 'success' : 'warning'">
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
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>流量分析图表</span>
                            </div>
                            <div id="trafficAnalysisChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>统计指标</span>
                            </div>
                            <el-table :data="statistics" style="width: 100%">
                                <el-table-column prop="metric" label="指标"></el-table-column>
                                <el-table-column prop="value" label="数值"></el-table-column>
                                <el-table-column prop="trend" label="趋势" width="100">
                                    <template slot-scope="scope">
                                        <i :class="getTrendIcon(scope.row.trend)" :style="{ color: getTrendColor(scope.row.trend) }"></i>
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
            alerts: [],
            statistics: [],
            baselineChart: null,
            trafficAnalysisChart: null
        }
    },
    methods: {
        async fetchData() {
            try {
                const response = await axios.get('/api/monitor/traffic');
                if (response.data.code === 200) {
                    const data = response.data.data;
                    this.alerts = data.alerts;
                    this.statistics = data.statistics;
                    this.updateCharts(data);
                }
            } catch (error) {
                this.$message.error('获取流量数据失败');
            }
        },
        updateCharts(data) {
            // 更新流量基线图表
            this.baselineChart.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['正常流量', '当前流量']
                },
                xAxis: {
                    type: 'category',
                    data: data.baseline.times
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: '正常流量',
                        type: 'line',
                        data: data.baseline.normal,
                        smooth: true
                    },
                    {
                        name: '当前流量',
                        type: 'line',
                        data: data.baseline.current,
                        smooth: true
                    }
                ]
            });

            // 更新流量分析图表
            this.trafficAnalysisChart.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['入站流量', '出站流量']
                },
                xAxis: {
                    type: 'category',
                    data: data.analysis.times
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: '入站流量',
                        type: 'bar',
                        data: data.analysis.inbound
                    },
                    {
                        name: '出站流量',
                        type: 'bar',
                        data: data.analysis.outbound
                    }
                ]
            });
        },
        initCharts() {
            this.baselineChart = echarts.init(document.getElementById('baselineChart'));
            this.trafficAnalysisChart = echarts.init(document.getElementById('trafficAnalysisChart'));
        },
        getSeverityType(severity) {
            const types = {
                '高': 'danger',
                '中': 'warning',
                '低': 'info'
            };
            return types[severity] || 'info';
        },
        getTrendIcon(trend) {
            return trend === 'up' ? 'el-icon-top' : 'el-icon-bottom';
        },
        getTrendColor(trend) {
            return trend === 'up' ? '#F56C6C' : '#67C23A';
        },
        refreshData() {
            this.fetchData();
        }
    },
    mounted() {
        this.initCharts();
        this.fetchData();
        // 定时刷新数据
        setInterval(this.refreshData, 30000);
        
        window.addEventListener('resize', () => {
            this.baselineChart && this.baselineChart.resize();
            this.trafficAnalysisChart && this.trafficAnalysisChart.resize();
        });
    }
}); 