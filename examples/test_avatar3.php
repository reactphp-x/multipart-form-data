<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReactphpX\MultipartFormData\FormFile;
use ReactphpX\MultipartFormData\MultiFormFile;
use ReactphpX\MultipartFormData\MultipartFormData;

echo "测试 avatar3 上传方式...\n\n";

// 创建包含不同上传方式的表单数据
$formData = new MultipartFormData([
    'name' => 'John',
    'age' => '30',
    // 传统单文件上传
    'avatar' => new FormFile(__DIR__ . '/upload_test1.txt'),
    'avatar2' => new FormFile(__DIR__ . '/upload_test2.txt'),
    // avatar3 多文件上传方式
    'avatar3' => new MultiFormFile([__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']),
]);

// 添加额外的多文件字段
$formData->addMultiFile('documents', [__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']);

echo "表单数据创建成功!\n";
echo "Headers: \n";
print_r($formData->getHeaders());

echo "\n测试 MultiFormFile 功能:\n";
$multiFile = new MultiFormFile([__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']);
echo "- 文件数量: " . $multiFile->getFileCount() . "\n";
echo "- 总大小: " . $multiFile->getTotalSize() . " bytes\n";
echo "- 文件路径: " . implode(', ', array_map('basename', $multiFile->getPaths())) . "\n";

echo "\navatar3 上传方式测试完成!\n";
echo "\n如要实际发送请求，请取消注释下面的代码:\n";
echo "/*\n";
echo "\$client = new ReactphpX\\MultipartFormData\\Browser();\n";
echo "\$promise = \$client->postFormData('http://localhost:8085', \$formData);\n";
echo "\$promise->then(function (\$response) {\n";
echo "    echo '响应状态码: ' . \$response->getStatusCode() . \"\\n\";\n";
echo "}, function (\$error) {\n";
echo "    echo '错误: ' . \$error->getMessage() . \"\\n\";\n";
echo "});\n";
echo "*/\n"; 