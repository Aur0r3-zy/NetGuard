<?php
// 获取统计数据
$stats = [
    'total_traffic' => $db->query("SELECT SUM(bytes) as total FROM traffic_data WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['total'] ?? 0,
    'total_packets' => $db->query("SELECT COUNT(*) as total FROM traffic_data WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['total'] ?? 0,
    'total_alerts' => $db->query("SELECT COUNT(*) as total FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['total'] ?? 0,
    'high_risk_ips' => $db->query("SELECT COUNT(DISTINCT source_ip) as total FROM alerts WHERE severity >= 8 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['total'] ?? 0
];

// 获取最近告警
$recent_alerts = $db->query("
    SELECT * FROM alerts 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// 获取流量趋势数据
$traffic_trend = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%H:00') as hour,
        SUM(bytes) as total_bytes,
        COUNT(*) as packet_count
    FROM traffic_data 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour
    ORDER BY hour
")->fetchAll();

// 获取攻击类型分布
$attack_types = $db->query("
    SELECT 
        attack_type,
        COUNT(*) as count
    FROM alerts 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY attack_type
")->fetchAll();

// 开始输出缓冲
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">系统概览</h2>
    
    <!-- 统计卡片 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">24小时总流量</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_traffic'] / 1024 / 1024, 2); ?> MB</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">数据包总数</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_packets']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">告警总数</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_alerts']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">高风险IP数</h5>
                    <h2 class="card-text"><?php echo number_format($stats['high_risk_ips']); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 图表区域 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">流量趋势</h5>
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">攻击类型分布</h5>
                    <canvas id="attackChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 最近告警 -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">最近告警</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>源IP</th>
                            <th>攻击类型</th>
                            <th>严重程度</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_alerts as $alert): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($alert['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($alert['source_ip']); ?></td>
                            <td><?php echo htmlspecialchars($alert['attack_type']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $alert['severity'] >= 8 ? 'danger' : ($alert['severity'] >= 5 ? 'warning' : 'info'); ?>">
                                    <?php echo $alert['severity']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $alert['status'] === 'resolved' ? 'success' : 'warning'; ?>">
                                    <?php echo $alert['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// 流量趋势图表
const trafficCtx = document.getElementById('trafficChart').getContext('2d');
new Chart(trafficCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($traffic_trend, 'hour')); ?>,
        datasets: [{
            label: '流量 (MB)',
            data: <?php echo json_encode(array_map(function($item) {
                return round($item['total_bytes'] / 1024 / 1024, 2);
            }, $traffic_trend)); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// 攻击类型分布图表
const attackCtx = document.getElementById('attackChart').getContext('2d');
new Chart(attackCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($attack_types, 'attack_type')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($attack_types, 'count')); ?>,
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
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 