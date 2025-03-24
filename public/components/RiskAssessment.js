Vue.component('RiskAssessment', {
    template: `
        <div class="risk-assessment">
            <el-card>
                <div slot="header">
                    <span>风险评估工具</span>
                    <el-button style="float: right" type="primary" size="small" @click="startScan">开始扫描</el-button>
                </div>

                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-card class="assessment-card">
                            <div slot="header">
                                <span>漏洞扫描</span>
                            </div>
                            <el-table :data="vulnerabilities" style="width: 100%">
                                <el-table-column prop="name" label="漏洞名称"></el-table-column>
                                <el-table-column prop="level" label="危险等级" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getVulnLevelType(scope.row.level)">
                                            {{ scope.row.level }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="scope.row.status === '已修复' ? 'success' : 'warning'">
                                            {{ scope.row.status }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="assessment-card">
                            <div slot="header">
                                <span>威胁评估</span>
                            </div>
                            <div id="threatChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                </el-row>

                <el-row :gutter="20" style="margin-top: 20px">
                    <el-col :span="12">
                        <el-card class="assessment-card">
                            <div slot="header">
                                <span>风险评分模型</span>
                            </div>
                            <div id="riskScoreChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="assessment-card">
                            <div slot="header">
                                <span>安全性报告</span>
                            </div>
                            <el-table :data="securityReport" style="width: 100%">
                                <el-table-column prop="category" label="类别"></el-table-column>
                                <el-table-column prop="score" label="得分" width="100">
                                    <template slot-scope="scope">
                                        <el-progress 
                                            :percentage="scope.row.score" 
                                            :color="getScoreColor(scope.row.score)">
                                        </el-progress>
                                    </template>
                                </el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getStatusType(scope.row.status)">
                                            {{ scope.row.status }}
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
            vulnerabilities: [],
            securityReport: [],
            threatChart: null,
            riskScoreChart: null
        }
    },
    methods: {
        async startScan() {
            try {
                const response = await axios.post('/api/risk/scan');
                if (response.data.code === 200) {
                    this.$message.success('扫描已开始');
                    this.fetchData();
                }
            } catch (error) {
                this.$message.error('启动扫描失败');
            }
        },
        async fetchData() {
            try {
                const response = await axios.get('/api/risk/assessment');
                if (response.data.code === 200) {
                    const data = response.data.data;
                    this.vulnerabilities = data.vulnerabilities;
                    this.securityReport = data.report;
                    this.updateCharts(data);
                }
            } catch (error) {
                this.$message.error('获取评估数据失败');
            }
        },
        updateCharts(data) {
            // 更新威胁评估图表
            this.threatChart.setOption({
                tooltip: {
                    trigger: 'item'
                },
                legend: {
                    orient: 'vertical',
                    left: 'left'
                },
                series: [
                    {
                        name: '威胁分布',
                        type: 'pie',
                        radius: '50%',
                        data: data.threats,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }
                ]
            });

            // 更新风险评分图表
            this.riskScoreChart.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                radar: {
                    indicator: data.riskIndicators
                },
                series: [
                    {
                        name: '风险评分',
                        type: 'radar',
                        data: [
                            {
                                value: data.riskScores,
                                name: '当前评分'
                            }
                        ]
                    }
                ]
            });
        },
        initCharts() {
            this.threatChart = echarts.init(document.getElementById('threatChart'));
            this.riskScoreChart = echarts.init(document.getElementById('riskScoreChart'));
        },
        getVulnLevelType(level) {
            const types = {
                '高危': 'danger',
                '中危': 'warning',
                '低危': 'info'
            };
            return types[level] || 'info';
        },
        getScoreColor(score) {
            if (score >= 80) return '#67C23A';
            if (score >= 60) return '#E6A23C';
            return '#F56C6C';
        },
        getStatusType(status) {
            const types = {
                '良好': 'success',
                '一般': 'warning',
                '较差': 'danger'
            };
            return types[status] || 'info';
        }
    },
    mounted() {
        this.initCharts();
        this.fetchData();
        
        window.addEventListener('resize', () => {
            this.threatChart && this.threatChart.resize();
            this.riskScoreChart && this.riskScoreChart.resize();
        });
    }
}); 