<?php

namespace Core\Auth;

class Authenticator {
    private $db;
    private $session;
    
    public function __construct($db) {
        $this->db = $db;
        $this->session = new \Core\Session\Session();
    }
    
    public function login($username, $password) {
        try {
            // 验证用户名和密码
            if ($username === 'User' && $password === '666888') {
                // 设置会话
                $this->session->set('user', [
                    'username' => $username,
                    'role' => 'admin',
                    'last_login' => time()
                ]);
                
                return [
                    'status' => 'success',
                    'message' => '登录成功'
                ];
            }
            
            return [
                'status' => 'error',
                'message' => '用户名或密码错误'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '登录失败：' . $e->getMessage()
            ];
        }
    }
    
    public function logout() {
        $this->session->destroy();
        return [
            'status' => 'success',
            'message' => '已退出登录'
        ];
    }
    
    public function isAuthenticated() {
        return $this->session->has('user');
    }
    
    public function getCurrentUser() {
        return $this->session->get('user');
    }
} 