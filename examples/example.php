<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReactphpX\MultipartFormData\Browser;
use ReactphpX\MultipartFormData\FormFile;
use ReactphpX\MultipartFormData\MultiFormFile;
use ReactphpX\MultipartFormData\MultipartFormData;

// 示例用法
/** @var Browser $client */
$client = (new Browser())->withTimeout(false);

// 创建多部分表单数据
$formData = new MultipartFormData([
    'name' => 'John',
    'age' => '30',
    'tags' => [
        'tag1',
        'tag2',
        'tag3',
    ],
    'nested' => [
        'nested1' => 'nested1',
        'nested2' => 'nested2',
        'nested3' => [
            'nested31' => 'nested31',
            'nested32' => 'nested32',
            'nested33' => [
                'nested331' => 'nested331',
                'nested332' => 'nested332',
            ],
        ],
    ],
    // 如果您有文件，可以这样添加：
    // 'photo' => __DIR__ . '/path/to/image.jpeg',
    'avatar' => new FormFile(__DIR__ . '/upload_test1.txt'),
    'avatar2' => new FormFile(__DIR__ . '/upload_test2.txt'),
    // avatar3 使用 MultiFormFile 支持多文件上传
    'avatar3' => new MultiFormFile([__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']),
]);

// 您也可以单独添加字段
$formData->addField('custom_field', 'custom_value');

// 现在支持外部添加数组字段！
$formData->addField('external_tags', ['external_tag1', 'external_tag2', 'external_tag3']);

// 支持复杂嵌套数组
$formData->addField('external_nested', [
    'level1' => ['item1', 'item2'],
    'level2' => 'simple_value',
    'complex' => [
        'deep1' => 'deep_value',
        'deep2' => ['deeper1', 'deeper2']
    ]
]);

// 或添加单个文件
// $formData->addFile('document', __DIR__ . '/upload_test2.txt');

// 或添加多个文件
$formData->addMultiFile('documents', [__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']);


// 发送请求
$url = 'http://localhost:8085';

$promise = $client->postFormData($url, $formData);

$promise->then(function ($response) {
    echo "响应状态码: " . $response->getStatusCode() . "\n";
    // echo "响应内容: " . $response->getBody() . "\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 如果您想在同步环境中使用，可以这样：
// \React\Async\await($promise); 