<?php

namespace Core\Network;

class TrafficAnalyzer {
    private $packetProcessor;
    private $trafficData;
    private $packetBufferSize;
    private $protocols;
    private $ports;
    private $socket;
    
    public function __construct($packetBufferSize = 1000, $protocols = [], $ports = []) {
        $this->packetProcessor = new PacketProcessor();
        $this->trafficData = [];
        $this->packetBufferSize = $packetBufferSize;
        $this->protocols = $protocols;
        $this->ports = $ports;
        $this->socket = null;
    }
    
    public function analyzeTraffic($packets) {
        // 处理数据包
        $processedPackets = $this->packetProcessor->process($packets);
        
        // 提取流量特征
        $features = $this->extractTrafficFeatures($processedPackets);
        
        // 更新流量数据
        $this->updateTrafficData($features);
        
        return [
            'status' => 'success',
            'features' => $features,
            'timestamp' => time()
        ];
    }
    
    public function capturePackets() {
        try {
            // 检查权限
            if (!function_exists('socket_create')) {
                throw new \Exception("Socket扩展未安装");
            }
            
            // 创建原始套接字
            $this->socket = socket_create(AF_INET, SOCK_RAW, IPPROTO_RAW);
            if ($this->socket === false) {
                throw new \Exception("无法创建套接字: " . socket_strerror(socket_last_error()));
            }
            
            // 设置套接字选项
            if (!socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0))) {
                throw new \Exception("无法设置套接字选项: " . socket_strerror(socket_last_error()));
            }
            
            // 设置缓冲区大小
            if (!socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 65535)) {
                throw new \Exception("无法设置缓冲区大小: " . socket_strerror(socket_last_error()));
            }
            
            // 接收数据包
            $packets = [];
            $startTime = microtime(true);
            $timeout = 1; // 1秒超时
            
            while (count($packets) < $this->packetBufferSize && (microtime(true) - $startTime) < $timeout) {
                $buffer = '';
                $packet = socket_recvfrom($this->socket, $buffer, 65535, 0, $ip, $port);
                
                if ($packet === false) {
                    $error = socket_last_error();
                    if ($error !== SOCKET_ETIMEDOUT) {
                        throw new \Exception("接收数据包失败: " . socket_strerror($error));
                    }
                    continue;
                }
                
                // 验证数据包大小
                if ($packet > 65535) {
                    continue;
                }
                
                // 验证IP地址格式
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    continue;
                }
                
                // 验证端口范围
                if ($port < 0 || $port > 65535) {
                    continue;
                }
                
                $packets[] = [
                    'raw_data' => $buffer,
                    'source_ip' => $ip,
                    'source_port' => $port,
                    'timestamp' => microtime(true),
                    'size' => $packet
                ];
            }
            
            // 处理捕获的数据包
            return $this->processCapturedPackets($packets);
            
        } catch (\Exception $e) {
            throw new \Exception("数据包捕获失败: " . $e->getMessage());
        } finally {
            if ($this->socket !== null) {
                socket_close($this->socket);
                $this->socket = null;
            }
        }
    }
    
    private function processCapturedPackets($packets) {
        $processedPackets = [];
        
        foreach ($packets as $packet) {
            // 验证协议
            if (!empty($this->protocols)) {
                $protocol = $this->detectProtocol($packet['raw_data']);
                if (!in_array($protocol, $this->protocols)) {
                    continue;
                }
            }
            
            // 验证端口
            if (!empty($this->ports)) {
                if (!in_array($packet['source_port'], $this->ports['common']) && 
                    !in_array($packet['source_port'], $this->ports['suspicious'])) {
                    continue;
                }
            }
            
            // 处理数据包
            $processedPacket = $this->packetProcessor->processPacket($packet);
            if ($processedPacket) {
                $processedPackets[] = $processedPacket;
            }
        }
        
        return $processedPackets;
    }
    
    private function detectProtocol($rawData) {
        // 实现协议检测逻辑
        // 这里使用简单的示例实现
        $protocolByte = ord($rawData[9]);
        switch ($protocolByte) {
            case 1:
                return 'ICMP';
            case 6:
                return 'TCP';
            case 17:
                return 'UDP';
            default:
                return 'UNKNOWN';
        }
    }
    
    private function extractTrafficFeatures($packets) {
        $features = [
            'packet_count' => count($packets),
            'byte_count' => 0,
            'protocol_distribution' => [],
            'port_distribution' => []
        ];
        
        foreach ($packets as $packet) {
            $features['byte_count'] += strlen($packet['data']);
            
            // 统计协议分布
            if (isset($packet['protocol'])) {
                $features['protocol_distribution'][$packet['protocol']] = 
                    ($features['protocol_distribution'][$packet['protocol']] ?? 0) + 1;
            }
            
            // 统计端口分布
            if (isset($packet['port'])) {
                $features['port_distribution'][$packet['port']] = 
                    ($features['port_distribution'][$packet['port']] ?? 0) + 1;
            }
        }
        
        return $features;
    }
    
    private function updateTrafficData($features) {
        $this->trafficData[] = [
            'timestamp' => time(),
            'features' => $features
        ];
        
        // 保持最近1000条记录
        if (count($this->trafficData) > 1000) {
            array_shift($this->trafficData);
        }
    }
    
    public function getTrafficData() {
        return $this->trafficData;
    }
} 