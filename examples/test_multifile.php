<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReactphpX\MultipartFormData\MultiFormFile;

echo "测试 MultiFormFile 类...\n";

// 创建多文件实例
$multiFile = new MultiFormFile([
    __DIR__ . '/upload_test1.txt',
    __DIR__ . '/upload_test2.txt'
]);

echo "文件数量: " . $multiFile->getFileCount() . "\n";
echo "总大小: " . $multiFile->getTotalSize() . " bytes\n";

$paths = $multiFile->getPaths();
echo "文件路径:\n";
foreach ($paths as $i => $path) {
    echo "  [$i] $path\n";
}

echo "\n测试内部 FormFile 实例:\n";
$formFiles = $multiFile->getFormFiles();
foreach ($formFiles as $i => $formFile) {
    echo "FormFile $i 头信息:\n";
    echo $formFile->getHeaders() . "\n";
}

echo "MultiFormFile 测试完成!\n"; 