# ReactPHP MultipartFormData

一个支持数组参数、文件流和带宽控制的 ReactPHP 多部分表单数据上传库。

## 功能特性

- ✅ **完整数组支持**: 支持简单数组和多维嵌套数组
- ✅ **文件流支持**: 使用 ReactPHP 流进行文件读取，支持大文件异步上传
- ✅ **带宽控制**: 可以精确控制上传速度，支持突发和持续速率限制
- ✅ **读取长度控制**: 可自定义数据块大小，优化内存使用
- ✅ **部分文件上传**: 支持上传文件的指定部分（断点续传等场景）
- ✅ **文件上传**: 支持单文件和多文件上传
- ✅ **灵活API**: 构造函数和外部方法都支持数组参数
- ✅ **标准兼容**: 生成符合 HTML 表单标准的字段名格式
- ✅ **高效处理**: 使用 `http_build_query` 简化数组处理逻辑
- ✅ **异步支持**: 基于 ReactPHP 的异步 HTTP 客户端

## 安装

```bash
composer require reactphp-x/multipart-form-data
```

## 基本使用示例

### 普通文件上传

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

## 高级功能示例

### 1. 带宽限制的文件上传

```php
// 限制上传速度为 512KB/s，使用 64KB 数据块
$limitedFile = FormFile::withBandwidthLimit(
    path: '/path/to/large-file.zip',
    maxBytesPerSecond: 512 * 1024, // 512KB/s
    chunkSizeKB: 64, // 64KB 块大小
    filename: 'limited_upload.zip'
);

// 查看文件信息
echo "文件大小: " . $limitedFile->getSizeInfo()['readable'] . " 字节\n";
echo "最大速度: " . $limitedFile->getBandwidthInfo()['maxSpeedMBps'] . " MB/s\n";
```

### 2. 部分文件上传

```php
// 只上传文件的指定部分，适用于断点续传等场景
$partialFile = FormFile::partial(
    path: '/path/to/file.txt',
    startPosition: 1024, // 从第 1024 字节开始
    length: 2048, // 只读取 2048 字节
    chunkSizeKB: 32 // 32KB 块大小
);

echo "总文件大小: " . $partialFile->getSizeInfo()['total'] . " 字节\n";
echo "将上传大小: " . $partialFile->getSizeInfo()['readable'] . " 字节\n";
```

### 3. 多文件带宽控制上传

```php
// 对多个文件统一进行带宽控制
$multiFiles = MultiFormFile::withBandwidthLimit(
    paths: ['/path/to/file1.txt', '/path/to/file2.txt', '/path/to/file3.txt'],
    maxBytesPerSecond: 1024 * 1024, // 1MB/s
    chunkSizeKB: 128, // 128KB 块大小
    contentType: 'text/plain'
);

// 获取详细信息
$sizeInfo = $multiFiles->getSizeInfo();
echo "文件总数: " . $sizeInfo['totalFiles'] . "\n";
echo "总大小: " . $sizeInfo['totalSize'] . " 字节\n";
echo "平均大小: " . $sizeInfo['averageSize'] . " 字节\n";
```

### 4. 完整表单数据示例

```php
$formData = new MultipartFormData([
    'user_name' => 'john_doe',
    'file_tags' => ['important', 'document', 'backup'],
    
    // 普通文件上传
    'avatar' => new FormFile('/path/to/avatar.jpg'),
    
    // 带宽限制的文件上传
    'document' => FormFile::withBandwidthLimit(
        path: '/path/to/large-document.pdf',
        maxBytesPerSecond: 256 * 1024, // 256KB/s
        chunkSizeKB: 64
    ),
    
    // 部分文件上传（例如文件预览）
    'preview' => FormFile::partial(
        path: '/path/to/image.jpg',
        startPosition: 0,
        length: 1024, // 只上传前 1KB 作为预览
        chunkSizeKB: 16
    ),
    
    // 多文件上传
    'attachments' => MultiFormFile::withBandwidthLimit(
        paths: ['/path/to/file1.txt', '/path/to/file2.txt'],
        maxBytesPerSecond: 512 * 1024, // 512KB/s
        chunkSizeKB: 128
    )
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

// 添加带宽限制的文件
$formData->addFile(
    name: 'controlled_upload',
    path: '/path/to/file.txt',
    bucketSize: 2 * 1024 * 1024, // 2MB 突发
    tokensPerInterval: 1024 * 1024, // 1MB/s 持续
    startPosition: 0,
    readLength: -1 // 整个文件
);
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

## 生成的字段格式

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

添加单个文件字段，支持带宽控制参数。

#### `addMultiFile(string $name, array $paths, ...)`

添加多个文件字段，支持带宽控制参数。

### FormFile 类

表示单个文件上传，支持文件流和带宽控制。

#### 构造函数

```php
new FormFile(
    string $path,                    // 文件路径
    ?string $filename = null,        // 自定义文件名
    ?string $contentType = null,     // 自定义内容类型
    int $bucketSize = 10485760,      // 令牌桶大小(10MB)
    int $tokensPerInterval = 1048576, // 每秒令牌数(1MB/s)
    int $startPosition = 0,          // 读取起始位置
    int $readLength = -1,            // 读取长度(-1=全部)
    int $chunkSizeKB = 1024          // 数据块大小(1MB)
)
```

#### 静态方法

```php
// 创建带宽限制的文件上传
FormFile::withBandwidthLimit(
    string $path,
    int $maxBytesPerSecond = 1048576, // 1MB/s
    int $chunkSizeKB = 1024,          // 1MB
    ?string $filename = null
): FormFile

// 创建部分文件上传
FormFile::partial(
    string $path,
    int $startPosition,
    int $length,
    int $chunkSizeKB = 1024
): FormFile
```

#### 实例方法

```php
// 获取文件大小信息
$file->getSizeInfo(): array // ['total' => int, 'readable' => int]

// 获取带宽配置信息
$file->getBandwidthInfo(): array // ['bucketSize' => int, 'tokensPerInterval' => int, 'maxSpeedMBps' => float, 'chunkSizeKB' => int]
```

### MultiFormFile 类

表示多个文件上传，支持统一的带宽控制。

#### 静态方法

```php
// 创建带宽限制的多文件上传
MultiFormFile::withBandwidthLimit(
    array $paths,
    int $maxBytesPerSecond = 1048576, // 1MB/s
    int $chunkSizeKB = 1024,          // 1MB
    ?string $contentType = null
): MultiFormFile
```

#### 实例方法

```php
// 获取所有文件的总大小信息
$multiFile->getSizeInfo(): array

// 获取带宽配置信息
$multiFile->getBandwidthInfo(): array

// 获取所有文件的详细信息
$multiFile->getFilesInfo(): array
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

## 性能优化建议

### 1. 带宽配置

```php
// 对于慢速网络连接
FormFile::withBandwidthLimit($path, 128 * 1024, 32); // 128KB/s, 32KB块

// 对于快速网络连接
FormFile::withBandwidthLimit($path, 10 * 1024 * 1024, 1024); // 10MB/s, 1MB块

// 对于移动网络
FormFile::withBandwidthLimit($path, 512 * 1024, 64); // 512KB/s, 64KB块
```

### 2. 内存优化

```php
// 大文件上传：使用较小的数据块减少内存占用
FormFile::withBandwidthLimit($path, 1024 * 1024, 128); // 128KB块

// 小文件上传：可以使用较大的数据块提高效率
FormFile::withBandwidthLimit($path, 2 * 1024 * 1024, 2048); // 2MB块
```

### 3. 部分上传优化

```php
// 断点续传示例
$fileSize = filesize('/path/to/large-file.zip');
$chunkSize = 1024 * 1024; // 1MB 块
$uploadedSize = 0; // 已上传大小（从数据库或其他存储获取）

while ($uploadedSize < $fileSize) {
    $remainingSize = $fileSize - $uploadedSize;
    $currentChunkSize = min($chunkSize, $remainingSize);
    
    $partialFile = FormFile::partial(
        path: '/path/to/large-file.zip',
        startPosition: $uploadedSize,
        length: $currentChunkSize,
        chunkSizeKB: 256 // 256KB 块
    );
    
    // 上传当前块...
    $uploadedSize += $currentChunkSize;
}
```

## 技术实现

本库使用 `http_build_query` + 字符串分割的方式来处理数组参数：

1. 使用 `http_build_query()` 将数组转换为查询字符串
2. 通过 `&` 分割字符串获取键值对
3. 通过 `=` 分割每个键值对（左边作为 key，右边作为 value）
4. 使用 `urldecode()` 解码 URL 编码

这种方法简洁高效，避免了复杂的递归逻辑，并且完全兼容 HTML 表单标准。

文件流处理基于 ReactPHP 的异步流系统和 `reactphp-x/bandwidth` 包的令牌桶算法，提供了精确的带宽控制能力。

## 要求

- PHP 8.1+
- ReactPHP HTTP 组件
- reactphp-x/bandwidth 组件

## 许可证

MIT 