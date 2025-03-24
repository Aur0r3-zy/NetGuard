<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use Core\Immune\Algorithm;
use Core\Network\TrafficAnalyzer;
use Data\Preprocessor;
use Utils\Database;
use Utils\Logger;

class SecurityTest extends TestCase {
    private $algorithm;
    private $trafficAnalyzer;
    private $preprocessor;
    private $db;
    private $logger;
    
    protected function setUp(): void {
        $this->algorithm = new Algorithm();
        $this->trafficAnalyzer = new TrafficAnalyzer();
        $this->preprocessor = new Preprocessor();
        $this->db = new Database('localhost', 3306, 'test_db', 'test_user', 'test_pass');
        $this->logger = new Logger('logs/test.log');
    }
    
    public function testInputValidation() {
        // 测试SQL注入防护
        $maliciousInput = "'; DROP TABLE users; --";
        $this->expectException(\InvalidArgumentException::class);
        $this->algorithm->analyze($maliciousInput);
        
        // 测试XSS防护
        $xssInput = "<script>alert('xss')</script>";
        $this->expectException(\InvalidArgumentException::class);
        $this->algorithm->analyze($xssInput);
    }
    
    public function testDataIntegrity() {
        // 测试数据完整性检查
        $data = [
            [
                'features' => [
                    'source_ip' => '192.168.1.1',
                    'target_ip' => '192.168.1.2',
                    'confidence' => 0.8
                ]
            ]
        ];
        
        $results = $this->algorithm->analyze($data);
        
        // 验证结果完整性
        $this->assertArrayHasKey('is_attack', $results[0]);
        $this->assertArrayHasKey('confidence', $results[0]);
        $this->assertArrayHasKey('details', $results[0]);
    }
    
    public function testAccessControl() {
        // 测试文件系统访问控制
        $this->expectException(\Exception::class);
        file_put_contents('/etc/passwd', 'test');
        
        // 测试数据库访问控制
        $this->expectException(\Exception::class);
        $this->db->query("SELECT * FROM mysql.user");
    }
    
    public function testLoggingSecurity() {
        // 测试日志注入防护
        $maliciousLog = "<?php system('rm -rf /'); ?>";
        $this->logger->info($maliciousLog);
        
        // 验证日志文件内容
        $logContent = file_get_contents('logs/test.log');
        $this->assertStringNotContainsString('<?php', $logContent);
    }
    
    public function testNetworkSecurity() {
        // 测试网络访问控制
        $this->expectException(\Exception::class);
        $this->trafficAnalyzer->capturePackets();
    }
    
    public function testConfigurationSecurity() {
        // 测试配置文件访问控制
        $this->expectException(\Exception::class);
        file_get_contents('/etc/shadow');
    }
    
    public function testMemoryManagement() {
        // 测试内存溢出防护
        $largeData = str_repeat('x', 1024 * 1024 * 100); // 100MB
        $this->expectException(\Exception::class);
        $this->algorithm->analyze($largeData);
    }
    
    public function testErrorHandling() {
        // 测试错误处理
        $this->expectException(\Exception::class);
        $this->algorithm->analyze(null);
    }
    
    protected function tearDown(): void {
        // 清理测试数据
        if (file_exists('logs/test.log')) {
            unlink('logs/test.log');
        }
    }
} 