<?php
// 开始输出缓冲
ob_start();
?>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 text-center">
            <h1 class="display-1">404</h1>
            <h2 class="mb-4">页面未找到</h2>
            <p class="lead mb-4">抱歉，您请求的页面不存在。</p>
            <a href="/" class="btn btn-primary">返回首页</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/resources/views/layout.php';
?> 