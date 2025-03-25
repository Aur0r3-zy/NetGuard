<?php
// 获取实时流量数据
$realtime_traffic = $db->query("
    SELECT 
        source_ip,
        destination_ip,
        protocol,
        port,
        bytes,
        created_at
    FROM traffic_data 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ORDER BY created_at DESC
    LIMIT 100
")->fetchAll();

// 获取实时告警
$realtime_alerts = $db->query("
    SELECT 
        source_ip,
        attack_type,
        severity,
        description,
        created_at
    FROM alerts 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ORDER BY created_at DESC
    LIMIT 50
")->fetchAll();

// 获取协议分布
$protocol_stats = $db->query("
    SELECT 
        protocol,
        COUNT(*) as count,
        SUM(bytes) as total_bytes
    FROM traffic_data 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    GROUP BY protocol
")->fetchAll();

// 开始输出缓冲
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">实时监控</h2>
    
    <!-- 实时流量 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">实时流量</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>源IP</th>
                                    <th>目标IP</th>
                                    <th>协议</th>
                                    <th>端口</th>
                                    <th>流量</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($realtime_traffic as $traffic): ?>
                                <tr>
                                    <td><?php echo date('H:i:s', strtotime($traffic['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($traffic['source_ip']); ?></td>
                                    <td><?php echo htmlspecialchars($traffic['destination_ip']); ?></td>
                                    <td><?php echo htmlspecialchars($traffic['protocol']); ?></td>
                                    <td><?php echo htmlspecialchars($traffic['port']); ?></td>
                                    <td><?php echo number_format($traffic['bytes'] / 1024, 2); ?> KB</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 协议分布 -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">协议分布</h5>
                    <canvas id="protocolChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 实时告警 -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">实时告警</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>源IP</th>
                            <th>攻击类型</th>
                            <th>严重程度</th>
                            <th>描述</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($realtime_alerts as $alert): ?>
                        <tr>
                            <td><?php echo date('H:i:s', strtotime($alert['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($alert['source_ip']); ?></td>
                            <td><?php echo htmlspecialchars($alert['attack_type']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $alert['severity'] >= 8 ? 'danger' : ($alert['severity'] >= 5 ? 'warning' : 'info'); ?>">
                                    <?php echo $alert['severity']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($alert['description']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// 协议分布图表
const protocolCtx = document.getElementById('protocolChart').getContext('2d');
new Chart(protocolCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($protocol_stats, 'protocol')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($protocol_stats, 'count')); ?>,
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(153, 102, 255)'
            ]
        }]
    },
    options: {
        responsive: true
    }
});

// 自动刷新页面
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 