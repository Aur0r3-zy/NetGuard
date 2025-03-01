const { createApp, ref } = Vue;
const { ElMessage } = ElementPlus;

const app = createApp({
    setup() {
        const activeIndex = ref('1');
        const currentUser = ref({
            username: '管理员',
            role: 'admin'
        });

        const navigateTo = (route) => {
            // 路由导航逻辑
            console.log('Navigating to:', route);
        };

        const showProfile = () => {
            ElMessage({
                message: '个人信息功能开发中...',
                type: 'info'
            });
        };

        const logout = async () => {
            try {
                await axios.post('/api/auth/logout');
                window.location.href = '/login.html';
            } catch (error) {
                ElMessage.error('退出登录失败');
            }
        };

        return {
            activeIndex,
            currentUser,
            navigateTo,
            showProfile,
            logout
        };
    }
});

app.use(ElementPlus);
app.mount('#app'); 