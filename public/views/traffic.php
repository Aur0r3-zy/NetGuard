<?php
ob_start();
?>

<div class="container-fluid">
    <!-- 时间范围选择 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="timeRangeForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">开始时间</label>
                            <input type="datetime-local" class="form-control" id="startTime" name="startTime">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">结束时间</label>
                            <input type="datetime-local" class="form-control" id="endTime" name="endTime">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">时间范围</label>
                            <select class="form-select" id="timeRange" name="timeRange">
                                <option value="1h">最近1小时</option>
                                <option value="6h">最近6小时</option>
                                <option value="24h">最近24小时</option>
                                <option value="7d">最近7天</option>
                                <option value="30d">最近30天</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">
                                <i class="fas fa-search me-1"></i>查询
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 流量统计 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">总流量</h5>
                    <h2 class="mb-0" id="total-traffic">0 B</h2>
                    <small class="text-muted">所选时间范围内的总流量</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">平均流量</h5>
                    <h2 class="mb-0" id="avg-traffic">0 B/s</h2>
                    <small class="text-muted">每秒平均流量</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">峰值流量</h5>
                    <h2 class="mb-0" id="peak-traffic">0 B/s</h2>
                    <small class="text-muted">最大瞬时流量</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">数据包数</h5>
                    <h2 class="mb-0" id="total-packets">0</h2>
                    <small class="text-muted">总数据包数量</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 流量图表 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">流量趋势</h5>
                    <canvas id="traffic-trend-chart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">协议分布</h5>
                    <canvas id="protocol-distribution-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 详细数据表格 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">流量详情</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>源IP</th>
                                    <th>目标IP</th>
                                    <th>协议</th>
                                    <th>端口</th>
                                    <th>数据包大小</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody id="traffic-table">
                                <!-- 数据将通过JavaScript动态加载 -->
                            </tbody>
                        </table>
                    </div>
                    <!-- 分页 -->
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center" id="pagination">
                            <!-- 分页将通过JavaScript动态生成 -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- 引入自定义API脚本 -->
<script src="/js/api.js"></script>
<!-- 引入流量分析脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 设置默认时间范围
    const now = new Date();
    const oneHourAgo = new Date(now - 3600000);
    
    document.getElementById('startTime').value = oneHourAgo.toISOString().slice(0, 16);
    document.getElementById('endTime').value = now.toISOString().slice(0, 16);
    
    // 时间范围选择事件
    document.getElementById('timeRange').addEventListener('change', function() {
        const range = this.value;
        const end = new Date();
        let start = new Date();
        
        switch(range) {
            case '1h':
                start.setHours(start.getHours() - 1);
                break;
            case '6h':
                start.setHours(start.getHours() - 6);
                break;
            case '24h':
                start.setDate(start.getDate() - 1);
                break;
            case '7d':
                start.setDate(start.getDate() - 7);
                break;
            case '30d':
                start.setDate(start.getDate() - 30);
                break;
        }
        
        document.getElementById('startTime').value = start.toISOString().slice(0, 16);
        document.getElementById('endTime').value = end.toISOString().slice(0, 16);
    });
    
    // 表单提交事件
    document.getElementById('timeRangeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadTrafficData();
    });
    
    // 初始加载数据
    loadTrafficData();
});

// 加载流量数据
function loadTrafficData() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    // 更新统计信息
    Api.getTrafficStats(startTime, endTime)
        .then(stats => {
            document.getElementById('total-traffic').textContent = formatBytes(stats.total_bytes);
            document.getElementById('avg-traffic').textContent = formatBytes(stats.avg_bytes_per_second) + '/s';
            document.getElementById('peak-traffic').textContent = formatBytes(stats.peak_bytes_per_second) + '/s';
            document.getElementById('total-packets').textContent = stats.total_packets;
        })
        .catch(error => console.error('加载流量统计失败:', error));
    
    // 更新流量趋势图
    Api.getTrafficTrend(startTime, endTime)
        .then(trend => {
            updateTrafficTrendChart(trend);
        })
        .catch(error => console.error('加载流量趋势失败:', error));
    
    // 更新协议分布图
    Api.getProtocolDistribution(startTime, endTime)
        .then(distribution => {
            updateProtocolDistributionChart(distribution);
        })
        .catch(error => console.error('加载协议分布失败:', error));
    
    // 加载详细数据
    loadTrafficTable(1);
}

// 更新协议分布图表
function updateProtocolDistributionChart(distribution) {
    const ctx = document.getElementById('protocol-distribution-chart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: distribution.map(d => d.protocol),
            datasets: [{
                data: distribution.map(d => d.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// 加载流量表格数据
function loadTrafficTable(page) {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    Api.getTrafficDetails(startTime, endTime, page)
        .then(data => {
            const tbody = document.getElementById('traffic-table');
            tbody.innerHTML = '';
            
            data.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${formatDateTime(item.created_at)}</td>
                    <td>${item.source_ip}</td>
                    <td>${item.destination_ip}</td>
                    <td>${item.protocol}</td>
                    <td>${item.port}</td>
                    <td>${formatBytes(item.bytes)}</td>
                    <td><span class="badge bg-${getStatusColor(item.status)}">${item.status}</span></td>
                `;
                tbody.appendChild(tr);
            });
            
            updatePagination(data.total_pages, page);
        })
        .catch(error => console.error('加载流量详情失败:', error));
}

// 更新分页
function updatePagination(totalPages, currentPage) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `
            <a class="page-link" href="#" onclick="loadTrafficTable(${i})">${i}</a>
        `;
        pagination.appendChild(li);
    }
}

// 工具函数
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('zh-CN');
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'success':
            return 'success';
        case 'error':
            return 'danger';
        case 'warning':
            return 'warning';
        default:
            return 'secondary';
    }
}
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/resources/views/layout.php';
?> 