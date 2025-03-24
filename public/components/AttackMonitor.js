Vue.component('AttackMonitor', {
    template: `
        <div class="attack-monitor">
            <el-card>
                <div slot="header">
                    <span>攻击模式监测</span>
                    <el-button style="float: right" type="primary" size="small" @click="refreshData">刷新数据</el-button>
                </div>
                
                <el-row :gutter="20">
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>实时流量监测</span>
                            </div>
                            <div id="trafficChart" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>异常行为识别</span>
                            </div>
                            <el-table :data="anomalyList" style="width: 100%">
                                <el-table-column prop="time" label="时间" width="180"></el-table-column>
                                <el-table-column prop="type" label="类型" width="120"></el-table-column>
                                <el-table-column prop="source" label="来源"></el-table-column>
                                <el-table-column prop="status" label="状态" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="scope.row.status === '已处理' ? 'success' : 'danger'">
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
                                <span>数据包分析</span>
                            </div>
                            <el-table :data="packetList" style="width: 100%">
                                <el-table-column prop="time" label="时间" width="180"></el-table-column>
                                <el-table-column prop="protocol" label="协议" width="100"></el-table-column>
                                <el-table-column prop="source" label="源地址"></el-table-column>
                                <el-table-column prop="destination" label="目标地址"></el-table-column>
                                <el-table-column prop="risk" label="风险等级" width="100">
                                    <template slot-scope="scope">
                                        <el-tag :type="getRiskType(scope.row.risk)">
                                            {{ scope.row.risk }}
                                        </el-tag>
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-card>
                    </el-col>
                    <el-col :span="12">
                        <el-card class="monitor-card">
                            <div slot="header">
                                <span>攻击源定位</span>
                            </div>
                            <div id="attackMap" style="height: 300px"></div>
                        </el-card>
                    </el-col>
                </el-row>
            </el-card>
        </div>
    `,
    data() {
        return {
            trafficChart: null,
            attackMap: null,
            anomalyList: [],
            packetList: []
        }
    },
    methods: {
        async fetchData() {
            try {
                const response = await axios.get('/api/monitor/attack');
                if (response.data.code === 200) {
                    const data = response.data.data;
                    this.anomalyList = data.anomalies;
                    this.packetList = data.packets;
                    this.updateCharts(data);
                }
            } catch (error) {
                this.$message.error('获取监测数据失败');
            }
        },
        updateCharts(data) {
            // 更新流量图表
            this.trafficChart.setOption({
                tooltip: {
                    trigger: 'axis'
                },
                xAxis: {
                    type: 'category',
                    data: data.traffic.times
                },
                yAxis: {
                    type: 'value'
                },
                series: [
                    {
                        name: '流量',
                        type: 'line',
                        data: data.traffic.values,
                        smooth: true
                    }
                ]
            });

            // 更新攻击地图
            this.attackMap.setOption({
                tooltip: {
                    trigger: 'item'
                },
                visualMap: {
                    min: 0,
                    max: 100,
                    text: ['高', '低'],
                    realtime: false,
                    calculable: true,
                    inRange: {
                        color: ['#e0f3f8', '#045a8d']
                    }
                },
                series: [
                    {
                        name: '攻击源分布',
                        type: 'map',
                        map: 'world',
                        data: data.attackSources
                    }
                ]
            });
        },
        initCharts() {
            // 初始化流量图表
            this.trafficChart = echarts.init(document.getElementById('trafficChart'));
            
            // 初始化攻击地图
            this.attackMap = echarts.init(document.getElementById('attackMap'));
            
            // 加载世界地图数据
            axios.get('https://cdn.bootcdn.net/ajax/libs/echarts/5.2.2/map/world.json').then(response => {
                echarts.registerMap('world', response.data);
            });
        },
        getRiskType(risk) {
            const types = {
                '高': 'danger',
                '中': 'warning',
                '低': 'success'
            };
            return types[risk] || 'info';
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
            this.trafficChart && this.trafficChart.resize();
            this.attackMap && this.attackMap.resize();
        });
    }
}); 