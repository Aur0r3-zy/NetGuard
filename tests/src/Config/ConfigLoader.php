<?php

namespace Config;

class ConfigLoader {
    private $config;
    private $configFile;
    
    public function __construct($configFile = 'config.json') {
        $this->configFile = $configFile;
        $this->config = [];
    }
    
    public function load() {
        if (!file_exists($this->configFile)) {
            throw new \Exception("Configuration file not found: {$this->configFile}");
        }
        
        $configData = file_get_contents($this->configFile);
        $this->config = json_decode($configData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in configuration file: " . json_last_error_msg());
        }
        
        return $this->config;
    }
    
    public function save() {
        $configData = json_encode($this->config, JSON_PRETTY_PRINT);
        return file_put_contents($this->configFile, $configData);
    }
    
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    public function has($key) {
        return isset($this->config[$key]);
    }
    
    public function remove($key) {
        unset($this->config[$key]);
    }
    
    public function getAll() {
        return $this->config;
    }
    
    public function merge($config) {
        $this->config = array_merge($this->config, $config);
    }
} 