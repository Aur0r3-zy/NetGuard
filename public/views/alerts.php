<?php
// 获取筛选参数
$severity = $_GET['severity'] ?? '';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 构建查询条件
$where = ['created_at BETWEEN ? AND ?'];
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if ($severity) {
    $where[] = 'severity = ?';
    $params[] = $severity;
}

if ($status) {
    $where[] = 'status = ?';
    $params[] = $status;
}

$where_clause = implode(' AND ', $where);

// 获取告警总数
$total = $db->prepare("SELECT COUNT(*) as total FROM alerts WHERE $where_clause");
$total->execute($params);
$total_alerts = $total->fetch()['total'];

// 分页
$page = max(1, $_GET['page'] ?? 1);
$per_page = 20;
$total_pages = ceil($total_alerts / $per_page);
$offset = ($page - 1) * $per_page;

// 获取告警列表
$alerts = $db->prepare("
    SELECT * FROM alerts 
    WHERE $where_clause
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$alerts->execute(array_merge($params, [$per_page, $offset]));
$alerts = $alerts->fetchAll();

// 获取告警统计
$stats = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN severity >= 8 THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN severity >= 5 AND severity < 8 THEN 1 ELSE 0 END) as warning,
        SUM(CASE WHEN severity < 5 THEN 1 ELSE 0 END) as info,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM alerts 
    WHERE $where_clause
");
$stats->execute($params);
$alert_stats = $stats->fetch();

// 开始输出缓冲
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">告警管理</h2>
    
    <!-- 筛选表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">严重程度</label>
                    <select name="severity" class="form-select">
                        <option value="">全部</option>
                        <option value="8" <?php echo $severity === '8' ? 'selected' : ''; ?>>严重 (8+)</option>
                        <option value="5" <?php echo $severity === '5' ? 'selected' : ''; ?>>警告 (5-7)</option>
                        <option value="1" <?php echo $severity === '1' ? 'selected' : ''; ?>>信息 (1-4)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-select">
                        <option value="">全部</option>
                        <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>未处理</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>已处理</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">开始日期</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">结束日期</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">筛选</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 告警统计 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">总告警数</h5>
                    <h2 class="card-text"><?php echo number_format($alert_stats['total']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">严重告警</h5>
                    <h2 class="card-text"><?php echo number_format($alert_stats['critical']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">警告告警</h5>
                    <h2 class="card-text"><?php echo number_format($alert_stats['warning']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">已处理告警</h5>
                    <h2 class="card-text"><?php echo number_format($alert_stats['resolved']); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 告警列表 -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">告警列表</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>源IP</th>
                            <th>攻击类型</th>
                            <th>严重程度</th>
                            <th>状态</th>
                            <th>描述</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
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
                            <td><?php echo htmlspecialchars($alert['description']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="resolveAlert(<?php echo $alert['id']; ?>)">
                                    标记已处理
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&severity=<?php echo $severity; ?>&status=<?php echo $status; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function resolveAlert(id) {
    if (confirm('确定要将此告警标记为已处理吗？')) {
        fetch('/api/alerts/' + id + '/resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('操作失败：' + data.message);
            }
        })
        .catch(error => {
            alert('操作失败：' + error);
        });
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 