<?php

namespace Utils;

class Logger {
    private $logFile;
    private $maxSize;
    private $backupCount;
    private $logLevel;
    
    private const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    public function __construct($logFile, $maxSize = 10485760, $backupCount = 5, $logLevel = 'INFO') {
        $this->logFile = $logFile;
        $this->maxSize = $maxSize;
        $this->backupCount = $backupCount;
        $this->logLevel = strtoupper($logLevel);
        
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        if (self::LEVELS[$level] < self::LEVELS[$this->logLevel]) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $logMessage .= " " . json_encode($context);
        }
        
        $logMessage .= PHP_EOL;
        
        $this->write($logMessage);
    }
    
    private function write($message) {
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->maxSize) {
            $this->rotate();
        }
        
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
    
    private function rotate() {
        for ($i = $this->backupCount - 1; $i >= 0; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        rename($this->logFile, $this->logFile . '.0');
    }
    
    public function setLogLevel($level) {
        $level = strtoupper($level);
        if (isset(self::LEVELS[$level])) {
            $this->logLevel = $level;
            return true;
        }
        return false;
    }
} 