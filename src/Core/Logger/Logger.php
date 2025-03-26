<?php

namespace App\Core\Logger;

class Logger {
    private $logFile;
    
    public function __construct($logFile = 'logs/app.log') {
        $this->logFile = $logFile;
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
    }
    
    public function error($message) {
        $this->log('ERROR', $message);
    }
    
    public function info($message) {
        $this->log('INFO', $message);
    }
    
    public function warning($message) {
        $this->log('WARNING', $message);
    }
    
    private function log($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
} 