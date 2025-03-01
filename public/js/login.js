const { createApp, ref } = Vue;
const { ElMessage } = ElementPlus;

const app = createApp({
    setup() {
        const loginForm = ref({
            username: '',
            password: ''
        });

        const loading = ref(false);

        const rules = {
            username: [
                { required: true, message: '请输入用户名', trigger: 'blur' },
                { min: 3, max: 20, message: '长度在 3 到 20 个字符', trigger: 'blur' }
            ],
            password: [
                { required: true, message: '请输入密码', trigger: 'blur' },
                { min: 6, max: 20, message: '长度在 6 到 20 个字符', trigger: 'blur' }
            ]
        };

        const handleLogin = async () => {
            try {
                loading.value = true;
                const response = await axios.post('/api/auth/login', loginForm.value);
                
                if (response.data.status === 'success') {
                    // 保存令牌
                    localStorage.setItem('token', response.data.token);
                    localStorage.setItem('user', JSON.stringify(response.data.user));
                    
                    ElMessage({
                        message: '登录成功',
                        type: 'success'
                    });
                    
                    // 延迟跳转以显示成功消息
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1000);
                } else {
                    throw new Error(response.data.message || '登录失败');
                }
            } catch (error) {
                ElMessage.error(error.response?.data?.message || error.message || '登录失败');
            } finally {
                loading.value = false;
            }
        };

        // 检查是否已登录
        const checkAuth = () => {
            const token = localStorage.getItem('token');
            if (token) {
                window.location.href = '/';
            }
        };

        // 页面加载时检查登录状态
        checkAuth();

        return {
            loginForm,
            loading,
            rules,
            handleLogin
        };
    }
});

app.use(ElementPlus);
app.mount('#app'); 