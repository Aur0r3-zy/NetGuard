<?php
// 开始输出缓冲
ob_start();
?>



<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 text-center">
            <h1 class="display-1">500</h1>
            <h2 class="mb-4">服务器错误</h2>
            <p class="lead mb-4">抱歉，服务器出现了问题。请稍后再试。</p>
            <?php if ($_ENV['APP_DEBUG'] ?? false): ?>
            <div class="alert alert-danger text-start">
                <h5>错误详情：</h5>
                <pre><?php echo htmlspecialchars($e->getMessage()); ?></pre>
                <pre><?php echo htmlspecialchars($e->getTraceAsString()); ?></pre>
            </div>
            <?php endif; ?>
            <a href="/" class="btn btn-primary">返回首页</a>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 