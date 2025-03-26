<?php
namespace App\Api;

class Request {
    public $user;
    public string $path;
    public string $method;
    public array $params = [];

    public function __construct(string $path, string $method) {
        $this->path = $path;
        $this->method = $method;
    }
    
    public function get($key, $default = null) {
        return $this->params[$key] ?? $default;
    }
    
    public function input($key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
    
    public function all() {
        return array_merge($_GET, $_POST);
    }
    
    public function has($key) {
        return isset($this->params[$key]);
    }
    
    public function isMethod($method) {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
    
    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getHeader($name) {
        $headers = getallheaders();
        return $headers[$name] ?? null;
    }
} 