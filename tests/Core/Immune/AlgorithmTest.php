<?php

namespace Tests\Core\Immune;

use PHPUnit\Framework\TestCase;
use Core\Immune\Algorithm;
use Core\Immune\Antigen;
use Core\Immune\Antibody;
use Core\Immune\Memory;

class AlgorithmTest extends TestCase {
    private $algorithm;
    
    protected function setUp(): void {
        $this->algorithm = new Algorithm(0.85, 1000, 0.7, 0.1);
    }
    
    public function testAnalyzeWithValidData() {
        $data = [
            [
                'features' => [
                    'source_ip' => '192.168.1.1',
                    'target_ip' => '192.168.1.2',
                    'port_scan' => true,
                    'confidence' => 0.8
                ]
            ]
        ];
        
        $results = $this->algorithm->analyze($data);
        
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertTrue($results[0]['is_attack']);
        $this->assertEquals('PORT_SCAN', $results[0]['attack_type']);
        $this->assertGreaterThanOrEqual(0, $results[0]['confidence']);
        $this->assertLessThanOrEqual(1, $results[0]['confidence']);
    }
    
    public function testAnalyzeWithInvalidData() {
        $this->expectException(\InvalidArgumentException::class);
        $this->algorithm->analyze('invalid_data');
    }
    
    public function testAnalyzeWithEmptyData() {
        $results = $this->algorithm->analyze([]);
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    public function testAnalyzeWithLowConfidenceData() {
        $data = [
            [
                'features' => [
                    'source_ip' => '192.168.1.1',
                    'target_ip' => '192.168.1.2',
                    'confidence' => 0.5
                ]
            ]
        ];
        
        $results = $this->algorithm->analyze($data);
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
    
    public function testAnalyzeWithMultipleAttackTypes() {
        $data = [
            [
                'features' => [
                    'source_ip' => '192.168.1.1',
                    'target_ip' => '192.168.1.2',
                    'dos_attack' => true,
                    'confidence' => 0.9
                ]
            ],
            [
                'features' => [
                    'source_ip' => '192.168.1.3',
                    'target_ip' => '192.168.1.4',
                    'sql_injection' => true,
                    'confidence' => 0.85
                ]
            ]
        ];
        
        $results = $this->algorithm->analyze($data);
        
        $this->assertCount(2, $results);
        $this->assertEquals('DOS_ATTACK', $results[0]['attack_type']);
        $this->assertEquals('SQL_INJECTION', $results[1]['attack_type']);
    }
} 