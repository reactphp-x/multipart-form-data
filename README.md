# ReactPHP Multipart Form Data

一个用于 ReactPHP 的多部分表单数据包装器，简化了文件上传和表单数据的处理。

## 安装

```bash
composer require reactphp-x/multipart-form-data
```

## 特性

- 🚀 基于 ReactPHP 的异步 HTTP 客户端
- 📁 支持文件上传
- 📝 支持普通表单字段
- 🔧 自动检测文件 MIME 类型
- ⚡ 简单易用的 API
- 🛡️ 类型安全（PHP 8.1+）

## 使用方法

### 基本用法

```php
<?php

use ReactphpX\MultipartFormData\Browser;
use ReactphpX\MultipartFormData\MultipartFormData;

$client = new Browser();

// 创建多部分表单数据
$formData = new MultipartFormData([
    'chat_id' => '123456',
    'message' => 'Hello World!',
    'photo' => '/path/to/image.jpeg', // 文件路径会自动检测为文件上传
]);

// 发送请求
$promise = $client->postFormData('https://api.example.com/upload', $formData);

$promise->then(function ($response) {
    echo "响应状态码: " . $response->getStatusCode() . "\n";
    echo "响应内容: " . $response->getBody() . "\n";
});
```

### 添加自定义字段

```php
$formData = new MultipartFormData();

// 添加普通文本字段
$formData->addField('username', 'john_doe');

// 添加文件
$formData->addFile('avatar', '/path/to/avatar.png', 'image/png');

// 添加带自定义属性的字段
$formData->addField(
    name: 'description',
    content: 'User description',
    contentType: 'text/plain',
    filename: 'description.txt'
);
```

### 使用自定义边界

```php
$formData = new MultipartFormData(
    fields: ['field1' => 'value1'],
    boundary: 'my-custom-boundary'
);
```

### 获取生成的数据

```php
// 获取请求头
$headers = $formData->getHeaders();
// 返回: ['Content-Type' => 'multipart/form-data; boundary=...', 'Content-Length' => '...']

// 获取请求体
$body = $formData->getBody();
```

## API 参考

### Browser 类

扩展了 `React\Http\Browser`，添加了 `postFormData` 方法。

#### `postFormData(string $url, MultipartFormData $formData, array $headers = [])`

发送多部分表单数据 POST 请求。

- `$url`: 请求 URL
- `$formData`: MultipartFormData 实例
- `$headers`: 可选的额外请求头

返回 `PromiseInterface<ResponseInterface>`

### MultipartFormData 类

#### `__construct(array $fields = [], ?string $boundary = null)`

创建新的多部分表单数据实例。

- `$fields`: 字段数组，可以是字符串、文件路径或 FormField 实例
- `$boundary`: 可选的自定义边界字符串

#### `addField(string $name, string $content, ?int $contentLength = null, ?string $contentType = null, ?string $filename = null)`

添加表单字段。

#### `addFile(string $name, string $path, ?string $contentType = null)`

添加文件字段。

#### `getBody(): string`

获取完整的请求体内容。

#### `getHeaders(): array`

获取必要的 HTTP 头。

### FormField 类

只读类，表示单个表单字段。

#### `__construct(string $content, ?int $contentLength = null, ?string $contentType = null, ?string $filename = null)`

创建表单字段实例。

## 要求

- PHP 8.1+
- ReactPHP HTTP 组件

## 许可证

MIT 许可证 