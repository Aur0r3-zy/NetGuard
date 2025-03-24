Vue.component('IntrusionRecords', {
    template: `
        <div class="intrusion-records">
            <el-card>
                <div slot="header">
                    <span>入侵数据记录</span>
                    <div style="float: right">
                        <el-button type="primary" size="small" @click="handleAdd">添加数据</el-button>
                        <el-button type="success" size="small" @click="handleExport">导出数据</el-button>
                    </div>
                </div>

                <el-form :inline="true" :model="searchForm" class="search-form">
                    <el-form-item label="攻击源IP">
                        <el-input v-model="searchForm.sourceIp" placeholder="请输入攻击源IP"></el-input>
                    </el-form-item>
                    <el-form-item label="目标IP">
                        <el-input v-model="searchForm.targetIp" placeholder="请输入目标IP"></el-input>
                    </el-form-item>
                    <el-form-item label="攻击类型">
                        <el-select v-model="searchForm.attackType" placeholder="请选择攻击类型">
                            <el-option label="全部" value=""></el-option>
                            <el-option v-for="type in attackTypes" :key="type" :label="type" :value="type"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="时间范围">
                        <el-date-picker
                            v-model="searchForm.timeRange"
                            type="daterange"
                            range-separator="至"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="handleSearch">搜索</el-button>
                        <el-button @click="resetSearch">重置</el-button>
                    </el-form-item>
                </el-form>

                <el-table
                    :data="records"
                    style="width: 100%"
                    @selection-change="handleSelectionChange">
                    <el-table-column type="selection" width="55"></el-table-column>
                    <el-table-column prop="time" label="入侵时间" width="180"></el-table-column>
                    <el-table-column prop="sourceIp" label="攻击源IP" width="150"></el-table-column>
                    <el-table-column prop="targetIp" label="目标IP" width="150"></el-table-column>
                    <el-table-column prop="attackType" label="攻击类型" width="120"></el-table-column>
                    <el-table-column prop="severity" label="严重程度" width="100">
                        <template slot-scope="scope">
                            <el-tag :type="getSeverityType(scope.row.severity)">
                                {{ scope.row.severity }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="处理状态" width="100">
                        <template slot-scope="scope">
                            <el-tag :type="getStatusType(scope.row.status)">
                                {{ scope.row.status }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="200">
                        <template slot-scope="scope">
                            <el-button type="text" @click="handleEdit(scope.row)">编辑</el-button>
                            <el-button type="text" @click="handleDelete(scope.row)">删除</el-button>
                            <el-button type="text" @click="handleViewDetails(scope.row)">查看详情</el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <div class="pagination-container">
                    <el-pagination
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange"
                        :current-page="currentPage"
                        :page-sizes="[10, 20, 50, 100]"
                        :page-size="pageSize"
                        layout="total, sizes, prev, pager, next, jumper"
                        :total="total">
                    </el-pagination>
                </div>
            </el-card>

            <!-- 添加/编辑对话框 -->
            <el-dialog :title="dialogTitle" :visible.sync="dialogVisible">
                <el-form :model="form" :rules="rules" ref="form" label-width="100px">
                    <el-form-item label="入侵时间" prop="time">
                        <el-date-picker
                            v-model="form.time"
                            type="datetime"
                            placeholder="选择日期时间">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item label="攻击源IP" prop="sourceIp">
                        <el-input v-model="form.sourceIp"></el-input>
                    </el-form-item>
                    <el-form-item label="目标IP" prop="targetIp">
                        <el-input v-model="form.targetIp"></el-input>
                    </el-form-item>
                    <el-form-item label="攻击类型" prop="attackType">
                        <el-select v-model="form.attackType">
                            <el-option v-for="type in attackTypes" :key="type" :label="type" :value="type"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="严重程度" prop="severity">
                        <el-select v-model="form.severity">
                            <el-option label="高" value="高"></el-option>
                            <el-option label="中" value="中"></el-option>
                            <el-option label="低" value="低"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="处理状态" prop="status">
                        <el-select v-model="form.status">
                            <el-option label="未处理" value="未处理"></el-option>
                            <el-option label="处理中" value="处理中"></el-option>
                            <el-option label="已处理" value="已处理"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="描述" prop="description">
                        <el-input type="textarea" v-model="form.description" rows="3"></el-input>
                    </el-form-item>
                </el-form>
                <div slot="footer" class="dialog-footer">
                    <el-button @click="dialogVisible = false">取 消</el-button>
                    <el-button type="primary" @click="handleSubmit">确 定</el-button>
                </div>
            </el-dialog>

            <!-- 详情对话框 -->
            <el-dialog title="入侵详情" :visible.sync="detailsVisible" width="60%">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="入侵时间">{{ details.time }}</el-descriptions-item>
                    <el-descriptions-item label="攻击源IP">{{ details.sourceIp }}</el-descriptions-item>
                    <el-descriptions-item label="目标IP">{{ details.targetIp }}</el-descriptions-item>
                    <el-descriptions-item label="攻击类型">{{ details.attackType }}</el-descriptions-item>
                    <el-descriptions-item label="严重程度">
                        <el-tag :type="getSeverityType(details.severity)">{{ details.severity }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="处理状态">
                        <el-tag :type="getStatusType(details.status)">{{ details.status }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="描述" :span="2">{{ details.description }}</el-descriptions-item>
                    <el-descriptions-item label="处理记录" :span="2">
                        <el-timeline>
                            <el-timeline-item
                                v-for="(activity, index) in details.activities"
                                :key="index"
                                :timestamp="activity.time"
                                :type="activity.type">
                                {{ activity.content }}
                            </el-timeline-item>
                        </el-timeline>
                    </el-descriptions-item>
                </el-descriptions>
            </el-dialog>
        </div>
    `,
    data() {
        return {
            records: [],
            selectedRecords: [],
            currentPage: 1,
            pageSize: 10,
            total: 0,
            attackTypes: [],
            searchForm: {
                sourceIp: '',
                targetIp: '',
                attackType: '',
                timeRange: []
            },
            dialogVisible: false,
            detailsVisible: false,
            dialogTitle: '',
            form: {
                time: '',
                sourceIp: '',
                targetIp: '',
                attackType: '',
                severity: '',
                status: '',
                description: ''
            },
            details: {},
            rules: {
                time: [{ required: true, message: '请选择入侵时间', trigger: 'change' }],
                sourceIp: [{ required: true, message: '请输入攻击源IP', trigger: 'blur' }],
                targetIp: [{ required: true, message: '请输入目标IP', trigger: 'blur' }],
                attackType: [{ required: true, message: '请选择攻击类型', trigger: 'change' }],
                severity: [{ required: true, message: '请选择严重程度', trigger: 'change' }],
                status: [{ required: true, message: '请选择处理状态', trigger: 'change' }]
            }
        }
    },
    methods: {
        async fetchData() {
            try {
                const params = {
                    page: this.currentPage,
                    pageSize: this.pageSize,
                    ...this.searchForm,
                    startTime: this.searchForm.timeRange[0],
                    endTime: this.searchForm.timeRange[1]
                };
                const response = await axios.get('/api/intrusion/records', { params });
                if (response.data.code === 200) {
                    const data = response.data.data;
                    this.records = data.records;
                    this.total = data.total;
                }
            } catch (error) {
                this.$message.error('获取入侵记录失败');
            }
        },
        async fetchAttackTypes() {
            try {
                const response = await axios.get('/api/intrusion/attack-types');
                if (response.data.code === 200) {
                    this.attackTypes = response.data.data;
                }
            } catch (error) {
                this.$message.error('获取攻击类型失败');
            }
        },
        handleSearch() {
            this.currentPage = 1;
            this.fetchData();
        },
        resetSearch() {
            this.searchForm = {
                sourceIp: '',
                targetIp: '',
                attackType: '',
                timeRange: []
            };
            this.handleSearch();
        },
        handleSizeChange(val) {
            this.pageSize = val;
            this.fetchData();
        },
        handleCurrentChange(val) {
            this.currentPage = val;
            this.fetchData();
        },
        handleSelectionChange(val) {
            this.selectedRecords = val;
        },
        handleAdd() {
            this.dialogTitle = '添加入侵记录';
            this.form = {
                time: new Date(),
                sourceIp: '',
                targetIp: '',
                attackType: '',
                severity: '',
                status: '未处理',
                description: ''
            };
            this.dialogVisible = true;
        },
        handleEdit(row) {
            this.dialogTitle = '编辑入侵记录';
            this.form = { ...row };
            this.dialogVisible = true;
        },
        async handleSubmit() {
            try {
                await this.$refs.form.validate();
                const url = this.form.id ? '/api/intrusion/records/' + this.form.id : '/api/intrusion/records';
                const method = this.form.id ? 'put' : 'post';
                const response = await axios[method](url, this.form);
                if (response.data.code === 200) {
                    this.$message.success('保存成功');
                    this.dialogVisible = false;
                    this.fetchData();
                }
            } catch (error) {
                this.$message.error('保存失败');
            }
        },
        async handleDelete(row) {
            try {
                await this.$confirm('确认删除该记录吗？', '提示', {
                    type: 'warning'
                });
                const response = await axios.delete('/api/intrusion/records/' + row.id);
                if (response.data.code === 200) {
                    this.$message.success('删除成功');
                    this.fetchData();
                }
            } catch (error) {
                if (error !== 'cancel') {
                    this.$message.error('删除失败');
                }
            }
        },
        async handleViewDetails(row) {
            try {
                const response = await axios.get('/api/intrusion/records/' + row.id);
                if (response.data.code === 200) {
                    this.details = response.data.data;
                    this.detailsVisible = true;
                }
            } catch (error) {
                this.$message.error('获取详情失败');
            }
        },
        async handleExport() {
            try {
                const response = await axios.get('/api/intrusion/records/export', {
                    params: this.searchForm,
                    responseType: 'blob'
                });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', '入侵记录.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                this.$message.error('导出失败');
            }
        },
        getSeverityType(severity) {
            const types = {
                '高': 'danger',
                '中': 'warning',
                '低': 'info'
            };
            return types[severity] || 'info';
        },
        getStatusType(status) {
            const types = {
                '已处理': 'success',
                '处理中': 'warning',
                '未处理': 'danger'
            };
            return types[status] || 'info';
        }
    },
    mounted() {
        this.fetchData();
        this.fetchAttackTypes();
    }
}); 