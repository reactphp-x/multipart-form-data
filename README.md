# ReactPHP MultipartFormData

一个支持数组参数的 ReactPHP 多部分表单数据上传库。

## 功能特性

- ✅ **完整数组支持**: 支持简单数组和多维嵌套数组
- ✅ **文件上传**: 支持单文件和多文件上传
- ✅ **灵活API**: 构造函数和外部方法都支持数组参数
- ✅ **标准兼容**: 生成符合 HTML 表单标准的字段名格式
- ✅ **高效处理**: 使用 `http_build_query` 简化数组处理逻辑
- ✅ **异步支持**: 基于 ReactPHP 的异步 HTTP 客户端

## 安装

```bash
composer require reactphp-x/multipart-form-data
```

## 使用示例

### 完整示例

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ReactphpX\MultipartFormData\Browser;
use ReactphpX\MultipartFormData\FormFile;
use ReactphpX\MultipartFormData\MultiFormFile;
use ReactphpX\MultipartFormData\MultipartFormData;

// 创建 HTTP 客户端
$client = (new Browser())->withTimeout(false);

// 创建多部分表单数据 - 支持数组参数！
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
    // 单文件上传
    'avatar' => new FormFile(__DIR__ . '/upload_test1.txt'),
    'avatar2' => new FormFile(__DIR__ . '/upload_test2.txt'),
    // 多文件上传
    'avatar3' => new MultiFormFile([__DIR__ . '/upload_test1.txt', __DIR__ . '/upload_test2.txt']),
]);
```

### 外部添加字段

```php
// 添加简单字段
$formData->addField('custom_field', 'custom_value');

// 外部添加数组字段 - 现在完全支持！
$formData->addField('external_tags', ['external_tag1', 'external_tag2', 'external_tag3']);

// 添加复杂嵌套数组
$formData->addField('external_nested', [
    'level1' => ['item1', 'item2'],
    'level2' => 'simple_value',
    'complex' => [
        'deep1' => 'deep_value',
        'deep2' => ['deeper1', 'deeper2']
    ]
]);
```

### 文件上传

```php
// 添加单个文件
$formData->addFile('document', __DIR__ . '/upload_test2.txt');

// 添加多个文件（使用相同字段名）
$formData->addMultiFile('documents', [
    __DIR__ . '/upload_test1.txt', 
    __DIR__ . '/upload_test2.txt'
]);
```

### 发送请求

```php
// 发送异步 POST 请求
$url = 'http://localhost:8085';
$promise = $client->postFormData($url, $formData);

$promise->then(function ($response) {
    echo "响应状态码: " . $response->getStatusCode() . "\n";
    echo "响应内容: " . $response->getBody() . "\n";
}, function ($error) {
    echo "错误: " . $error->getMessage() . "\n";
});

// 如果需要同步处理，可以使用：
// \React\Async\await($promise);
```

### 生成的字段格式

数组会自动转换为标准的 HTML 表单字段名格式：

```
name => John
age => 30
tags[0] => tag1
tags[1] => tag2
tags[2] => tag3
nested[nested1] => nested1
nested[nested2] => nested2
nested[nested3][nested31] => nested31
nested[nested3][nested32] => nested32
nested[nested3][nested33][nested331] => nested331
nested[nested3][nested33][nested332] => nested332
external_tags[0] => external_tag1
external_tags[1] => external_tag2
external_tags[2] => external_tag3
external_nested[level1][0] => item1
external_nested[level1][1] => item2
external_nested[level2] => simple_value
external_nested[complex][deep1] => deep_value
external_nested[complex][deep2][0] => deeper1
external_nested[complex][deep2][1] => deeper2
```

## API 参考

### Browser 类

扩展了 `React\Http\Browser`，添加了表单数据上传支持。

#### `postFormData(string $url, MultipartFormData $formData, array $headers = [])`

发送多部分表单数据 POST 请求。

- `$url`: 请求 URL
- `$formData`: MultipartFormData 实例
- `$headers`: 可选的额外请求头

返回 `PromiseInterface<ResponseInterface>`

### MultipartFormData 类

#### `__construct(array $fields = [], ?string $boundary = null)`

创建新的多部分表单数据实例。

- `$fields`: 字段数组，支持字符串、数组、FormField、FormFile 或 MultiFormFile 实例
- `$boundary`: 可选的自定义边界字符串

#### `addField(string $name, string|array $content, ...)`

添加表单字段，**现在支持数组参数**。

#### `addFile(string $name, string $path, ...)`

添加单个文件字段。

#### `addMultiFile(string $name, array $paths, ...)`

添加多个文件字段。

### FormFile 类

表示单个文件上传。

```php
$file = new FormFile('/path/to/file.txt');
```

### MultiFormFile 类

表示多个文件上传。

```php
$multiFile = new MultiFormFile([
    '/path/to/file1.txt',
    '/path/to/file2.txt'
]);
```

## 开发

### 运行测试

```bash
# 安装依赖
composer install

# 运行测试
./vendor/bin/phpunit

# 运行带详细输出的测试
./vendor/bin/phpunit --testdox
```

### 测试覆盖的功能

- ✅ 构造函数数组支持
- ✅ 外部 addField 数组支持  
- ✅ 混合字段类型（数组 + 文件）
- ✅ 深层嵌套数组处理
- ✅ http_build_query 一致性
- ✅ 单文件和多文件上传
- ✅ 头部信息生成
- ✅ 边界情况处理

## 技术实现

本库使用 `http_build_query` + 字符串分割的方式来处理数组参数：

1. 使用 `http_build_query()` 将数组转换为查询字符串
2. 通过 `&` 分割字符串获取键值对
3. 通过 `=` 分割每个键值对（左边作为 key，右边作为 value）
4. 使用 `urldecode()` 解码 URL 编码

这种方法简洁高效，避免了复杂的递归逻辑，并且完全兼容 HTML 表单标准。

## 要求

- PHP 8.1+
- ReactPHP HTTP 组件

## 许可证

MIT 