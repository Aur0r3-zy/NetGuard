<?php

use App\Api\Controller\DashboardController;
use App\Api\Controller\IntrusionController;
use App\Api\Controller\MonitorController;
use App\Api\Controller\RiskController;

// 仪表盘路由
$router->get('/api/dashboard/statistics', [DashboardController::class, 'getStatistics']);

// 入侵记录路由
$router->get('/api/intrusion/records', [IntrusionController::class, 'getRecords']);
$router->get('/api/intrusion/records/{id}', [IntrusionController::class, 'getRecord']);
$router->post('/api/intrusion/records', [IntrusionController::class, 'createRecord']);
$router->put('/api/intrusion/records/{id}', [IntrusionController::class, 'updateRecord']);
$router->delete('/api/intrusion/records/{id}', [IntrusionController::class, 'deleteRecord']);
$router->get('/api/intrusion/attack-types', [IntrusionController::class, 'getAttackTypes']);
$router->get('/api/intrusion/records/export', [IntrusionController::class, 'exportRecords']);

// 监控路由
$router->get('/api/monitor/attack', [MonitorController::class, 'getAttackData']);
$router->get('/api/monitor/traffic', [MonitorController::class, 'getTrafficData']);
$router->get('/api/monitor/security', [MonitorController::class, 'getSecurityData']);
$router->get('/api/monitor/anomalies', [MonitorController::class, 'getAnomalies']);

// 风险评估路由
$router->post('/api/risk/scan', [RiskController::class, 'scanVulnerabilities']);
$router->get('/api/risk/assessment', [RiskController::class, 'getAssessment']);
$router->get('/api/risk/score', [RiskController::class, 'getRiskScore']); 