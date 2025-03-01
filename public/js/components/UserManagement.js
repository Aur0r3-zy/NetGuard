const UserManagement = {
    template: `
        <div class="user-management">
            <el-card>
                <template #header>
                    <div class="card-header">
                        <span>用户管理</span>
                        <el-button type="primary" @click="showCreateDialog">
                            <i class="el-icon-plus"></i> 新建用户
                        </el-button>
                    </div>
                </template>

                <!-- 搜索栏 -->
                <el-form :inline="true" :model="searchForm" class="search-form">
                    <el-form-item>
                        <el-input
                            v-model="searchForm.keyword"
                            placeholder="搜索用户名/邮箱"
                            prefix-icon="el-icon-search"
                            clearable
                            @clear="handleSearch"
                            @keyup.enter="handleSearch">
                        </el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-select v-model="searchForm.role" placeholder="角色" clearable @change="handleSearch">
                            <el-option label="管理员" value="admin"></el-option>
                            <el-option label="普通用户" value="user"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-select v-model="searchForm.status" placeholder="状态" clearable @change="handleSearch">
                            <el-option label="启用" :value="1"></el-option>
                            <el-option label="禁用" :value="0"></el-option>
                        </el-select>
                    </el-form-item>
                </el-form>

                <!-- 用户列表 -->
                <el-table
                    :data="users"
                    style="width: 100%"
                    v-loading="loading">
                    <el-table-column prop="username" label="用户名" width="150"></el-table-column>
                    <el-table-column prop="email" label="邮箱" width="200"></el-table-column>
                    <el-table-column prop="role" label="角色" width="100">
                        <template #default="scope">
                            <el-tag :type="scope.row.role === 'admin' ? 'danger' : 'success'">
                                {{ scope.row.role === 'admin' ? '管理员' : '普通用户' }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="状态" width="100">
                        <template #default="scope">
                            <el-switch
                                v-model="scope.row.status"
                                :active-value="1"
                                :inactive-value="0"
                                @change="handleStatusChange(scope.row)">
                            </el-switch>
                        </template>
                    </el-table-column>
                    <el-table-column prop="created_at" label="创建时间" width="180">
                        <template #default="scope">
                            {{ formatDate(scope.row.created_at) }}
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="250">
                        <template #default="scope">
                            <el-button
                                size="small"
                                @click="showEditDialog(scope.row)">
                                编辑
                            </el-button>
                            <el-button
                                size="small"
                                type="success"
                                @click="showPermissionDialog(scope.row)">
                                权限
                            </el-button>
                            <el-button
                                size="small"
                                type="danger"
                                @click="handleDelete(scope.row)">
                                删除
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <!-- 分页 -->
                <div class="pagination-container">
                    <el-pagination
                        background
                        layout="total, sizes, prev, pager, next"
                        :total="total"
                        :page-size="pageSize"
                        :current-page="currentPage"
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange">
                    </el-pagination>
                </div>

                <!-- 创建/编辑用户对话框 -->
                <el-dialog
                    :title="dialogType === 'create' ? '创建用户' : '编辑用户'"
                    v-model="dialogVisible"
                    width="500px">
                    <el-form
                        ref="userForm"
                        :model="userForm"
                        :rules="userRules"
                        label-width="100px">
                        <el-form-item label="用户名" prop="username">
                            <el-input v-model="userForm.username"></el-input>
                        </el-form-item>
                        <el-form-item label="邮箱" prop="email">
                            <el-input v-model="userForm.email"></el-input>
                        </el-form-item>
                        <el-form-item label="密码" prop="password" v-if="dialogType === 'create'">
                            <el-input v-model="userForm.password" type="password" show-password></el-input>
                        </el-form-item>
                        <el-form-item label="角色" prop="role">
                            <el-select v-model="userForm.role" placeholder="请选择角色">
                                <el-option label="管理员" value="admin"></el-option>
                                <el-option label="普通用户" value="user"></el-option>
                            </el-select>
                        </el-form-item>
                    </el-form>
                    <template #footer>
                        <span class="dialog-footer">
                            <el-button @click="dialogVisible = false">取消</el-button>
                            <el-button type="primary" @click="handleSubmit" :loading="submitting">
                                {{ submitting ? '提交中...' : '确定' }}
                            </el-button>
                        </span>
                    </template>
                </el-dialog>

                <!-- 权限设置对话框 -->
                <el-dialog
                    title="设置权限"
                    v-model="permissionDialogVisible"
                    width="600px">
                    <el-transfer
                        v-model="selectedPermissions"
                        :data="allPermissions"
                        :titles="['可用权限', '已分配权限']">
                    </el-transfer>
                    <template #footer>
                        <span class="dialog-footer">
                            <el-button @click="permissionDialogVisible = false">取消</el-button>
                            <el-button type="primary" @click="handlePermissionSubmit" :loading="permissionSubmitting">
                                确定
                            </el-button>
                        </span>
                    </template>
                </el-dialog>
            </el-card>
        </div>
    `,

    setup() {
        const users = ref([]);
        const loading = ref(false);
        const total = ref(0);
        const pageSize = ref(10);
        const currentPage = ref(1);
        const dialogVisible = ref(false);
        const dialogType = ref('create');
        const submitting = ref(false);
        const permissionDialogVisible = ref(false);
        const permissionSubmitting = ref(false);
        const selectedPermissions = ref([]);
        const allPermissions = ref([]);
        const currentUserId = ref(null);

        const searchForm = ref({
            keyword: '',
            role: '',
            status: ''
        });

        const userForm = ref({
            username: '',
            email: '',
            password: '',
            role: 'user'
        });

        const userRules = {
            username: [
                { required: true, message: '请输入用户名', trigger: 'blur' },
                { min: 3, max: 20, message: '长度在 3 到 20 个字符', trigger: 'blur' }
            ],
            email: [
                { required: true, message: '请输入邮箱地址', trigger: 'blur' },
                { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' }
            ],
            password: [
                { required: true, message: '请输入密码', trigger: 'blur' },
                { min: 6, max: 20, message: '长度在 6 到 20 个字符', trigger: 'blur' }
            ],
            role: [
                { required: true, message: '请选择角色', trigger: 'change' }
            ]
        };

        // 获取用户列表
        const fetchUsers = async () => {
            try {
                loading.value = true;
                const response = await axios.get('/api/users', {
                    params: {
                        page: currentPage.value,
                        limit: pageSize.value,
                        ...searchForm.value
                    }
                });
                users.value = response.data.data;
                total.value = response.data.total;
            } catch (error) {
                ElMessage.error('获取用户列表失败');
            } finally {
                loading.value = false;
            }
        };

        // 获取所有权限
        const fetchPermissions = async () => {
            try {
                const response = await axios.get('/api/permissions');
                allPermissions.value = response.data.map(item => ({
                    key: item.id,
                    label: item.name,
                    description: item.description
                }));
            } catch (error) {
                ElMessage.error('获取权限列表失败');
            }
        };

        // 获取用户权限
        const fetchUserPermissions = async (userId) => {
            try {
                const response = await axios.get(`/api/users/${userId}/permissions`);
                selectedPermissions.value = response.data.map(item => item.id);
            } catch (error) {
                ElMessage.error('获取用户权限失败');
            }
        };

        // 显示创建对话框
        const showCreateDialog = () => {
            dialogType.value = 'create';
            userForm.value = {
                username: '',
                email: '',
                password: '',
                role: 'user'
            };
            dialogVisible.value = true;
        };

        // 显示编辑对话框
        const showEditDialog = (row) => {
            dialogType.value = 'edit';
            userForm.value = {
                ...row,
                password: ''
            };
            dialogVisible.value = true;
        };

        // 显示权限对话框
        const showPermissionDialog = async (row) => {
            currentUserId.value = row.id;
            await fetchUserPermissions(row.id);
            permissionDialogVisible.value = true;
        };

        // 提交用户表单
        const handleSubmit = async () => {
            try {
                submitting.value = true;
                if (dialogType.value === 'create') {
                    await axios.post('/api/users', userForm.value);
                    ElMessage.success('创建用户成功');
                } else {
                    await axios.put(`/api/users/${userForm.value.id}`, userForm.value);
                    ElMessage.success('更新用户成功');
                }
                dialogVisible.value = false;
                fetchUsers();
            } catch (error) {
                ElMessage.error(error.response?.data?.message || '操作失败');
            } finally {
                submitting.value = false;
            }
        };

        // 提交权限设置
        const handlePermissionSubmit = async () => {
            try {
                permissionSubmitting.value = true;
                await axios.post(`/api/users/${currentUserId.value}/permissions`, {
                    permissions: selectedPermissions.value
                });
                ElMessage.success('权限设置成功');
                permissionDialogVisible.value = false;
            } catch (error) {
                ElMessage.error('权限设置失败');
            } finally {
                permissionSubmitting.value = false;
            }
        };

        // 删除用户
        const handleDelete = async (row) => {
            try {
                await ElMessageBox.confirm('确定要删除该用户吗？', '提示', {
                    type: 'warning'
                });
                await axios.delete(`/api/users/${row.id}`);
                ElMessage.success('删除成功');
                fetchUsers();
            } catch (error) {
                if (error !== 'cancel') {
                    ElMessage.error('删除失败');
                }
            }
        };

        // 更改用户状态
        const handleStatusChange = async (row) => {
            try {
                await axios.put(`/api/users/${row.id}`, {
                    status: row.status
                });
                ElMessage.success('状态更新成功');
            } catch (error) {
                ElMessage.error('状态更新失败');
                row.status = !row.status;
            }
        };

        // 搜索
        const handleSearch = () => {
            currentPage.value = 1;
            fetchUsers();
        };

        // 分页
        const handleSizeChange = (val) => {
            pageSize.value = val;
            fetchUsers();
        };

        const handleCurrentChange = (val) => {
            currentPage.value = val;
            fetchUsers();
        };

        // 格式化日期
        const formatDate = (date) => {
            return new Date(date).toLocaleString();
        };

        // 初始化
        onMounted(() => {
            fetchUsers();
            fetchPermissions();
        });

        return {
            users,
            loading,
            total,
            pageSize,
            currentPage,
            dialogVisible,
            dialogType,
            submitting,
            permissionDialogVisible,
            permissionSubmitting,
            selectedPermissions,
            allPermissions,
            searchForm,
            userForm,
            userRules,
            showCreateDialog,
            showEditDialog,
            showPermissionDialog,
            handleSubmit,
            handlePermissionSubmit,
            handleDelete,
            handleStatusChange,
            handleSearch,
            handleSizeChange,
            handleCurrentChange,
            formatDate
        };
    }
}; 