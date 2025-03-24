<?php

namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use Core\Immune\Algorithm;
use Core\Network\TrafficAnalyzer;
use Data\Preprocessor;

class PerformanceTest extends TestCase {
    private $algorithm;
    private $trafficAnalyzer;
    private $preprocessor;
    
    protected function setUp(): void {
        $this->algorithm = new Algorithm();
        $this->trafficAnalyzer = new TrafficAnalyzer();
        $this->preprocessor = new Preprocessor();
    }
    
    public function testPacketProcessingPerformance() {
        // 生成测试数据
        $packets = $this->generateTestPackets(1000);
        
        // 测量处理时间
        $startTime = microtime(true);
        $processedPackets = $this->trafficAnalyzer->analyzeTraffic($packets);
        $endTime = microtime(true);
        
        $processingTime = $endTime - $startTime;
        
        // 验证性能指标
        $this->assertLessThan(1.0, $processingTime, "数据包处理时间不应超过1秒");
        $this->assertCount(1000, $processedPackets['features']['packet_count'], "应处理所有数据包");
    }
    
    public function testMemoryUsage() {
        // 生成大量测试数据
        $packets = $this->generateTestPackets(10000);
        
        // 测量内存使用
        $startMemory = memory_get_usage(true);
        $this->algorithm->analyze($packets);
        $endMemory = memory_get_usage(true);
        
        $memoryUsed = $endMemory - $startMemory;
        
        // 验证内存使用
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, "内存使用不应超过100MB");
    }
    
    public function testConcurrentProcessing() {
        // 模拟并发请求
        $requests = [];
        for ($i = 0; $i < 10; $i++) {
            $requests[] = $this->generateTestPackets(100);
        }
        
        $startTime = microtime(true);
        $results = [];
        
        foreach ($requests as $request) {
            $results[] = $this->algorithm->analyze($request);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // 验证并发处理性能
        $this->assertLessThan(5.0, $totalTime, "并发处理时间不应超过5秒");
        $this->assertCount(10, $results, "应处理所有并发请求");
    }
    
    public function testResponseTime() {
        // 测试响应时间
        $packets = $this->generateTestPackets(100);
        
        $startTime = microtime(true);
        $result = $this->algorithm->analyze($packets);
        $endTime = microtime(true);
        
        $responseTime = $endTime - $startTime;
        
        // 验证响应时间
        $this->assertLessThan(0.5, $responseTime, "响应时间不应超过500毫秒");
        $this->assertArrayHasKey('is_attack', $result[0], "结果应包含攻击检测信息");
    }
    
    private function generateTestPackets($count) {
        $packets = [];
        for ($i = 0; $i < $count; $i++) {
            $packets[] = [
                'features' => [
                    'source_ip' => '192.168.1.' . rand(1, 254),
                    'target_ip' => '192.168.1.' . rand(1, 254),
                    'port' => rand(1, 65535),
                    'protocol' => rand(1, 17),
                    'size' => rand(64, 1500),
                    'timestamp' => microtime(true)
                ]
            ];
        }
        return $packets;
    }
} 