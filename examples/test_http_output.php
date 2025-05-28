<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReactphpX\MultipartFormData\FormFile;
use ReactphpX\MultipartFormData\MultiFormFile;
use ReactphpX\MultipartFormData\MultipartFormData;

echo "展示 avatar3 上传方式的 HTTP 输出格式...\n\n";

// 创建表单数据
$formData = new MultipartFormData([
    'name' => 'John',
    'avatar' => new FormFile(__DIR__ . '/upload_test1.txt'),
    'avatar3' => new MultiFormFile([__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']),
]);

echo "=== HTTP Headers ===\n";
$headers = $formData->getHeaders();
foreach ($headers as $name => $value) {
    echo "{$name}: {$value}\n";
}

echo "\n=== HTTP Body (前200字符) ===\n";

// 获取 body 流
$body = $formData->getBody();

$output = '';
$body->on('data', function ($data) use (&$output) {
    $output .= $data;
});

$body->on('end', function () use (&$output) {
    // 只显示前200字符，避免输出过长
    $preview = substr($output, 0, 500);
    echo $preview;
    if (strlen($output) > 500) {
        echo "\n\n... (输出被截断, 总长度: " . strlen($output) . " 字符) ...\n";
    }
    echo "\n\n=== 说明 ===\n";
    echo "- 'name' 字段: 普通文本字段\n";
    echo "- 'avatar' 字段: 单文件上传 (使用 FormFile)\n";
    echo "- 'avatar3[]' 字段: 多文件上传 (使用 MultiFormFile)\n";
    echo "  - 每个文件作为独立的 multipart 部分\n";
    echo "  - 字段名使用 'avatar3[]' 格式\n";
    echo "  - 服务器端可以通过 \$_FILES['avatar3'] 数组接收\n";
});

$body->on('error', function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 让 ReactPHP 事件循环运行
\React\EventLoop\Loop::get()->run(); 