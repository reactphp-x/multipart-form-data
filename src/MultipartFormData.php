<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use Exception;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use React\Promise\Deferred;
use function React\Async\async;
use function React\Async\await;


class MultipartFormData
{
    /** @var FormField[]|FormFile[]|MultiFormFile[] */
    private array $fields = [];

    private string $boundary;

    public function __construct(
        array $fields = [],
        ?string $boundary = null
    ) {
        try {
            $this->boundary = $boundary ?? \bin2hex(\random_bytes(16));
        } catch (\Exception $exception) {
            throw new Exception('Failed to obtain random boundary', 0, $exception);
        }

        // 分离文件字段和普通字段
        $regularFields = [];
        
        foreach ($fields as $name => $field) {
            if ($field instanceof FormField || $field instanceof FormFile || $field instanceof MultiFormFile) {
                $this->fields[(string)$name] = $field;
            } else {
                // 收集非文件字段到数组中，稍后用 http_build_query 处理
                $regularFields[$name] = $field;
            }
        }
        
        // 使用 http_build_query 处理所有非文件字段
        if (!empty($regularFields)) {
            $this->processRegularFields($regularFields);
        }
    }

    public function addField(
        string $name,
        string|array $content,
        ?int $contentLength = null,
        ?string $contentType = null,
        ?string $filename = null,
    ): void {
        // 如果内容是数组，使用 http_build_query 处理
        if (is_array($content)) {
            $this->processRegularFields([$name => $content]);
        } else {
            // 简单字符串字段
            $this->fields[$name] = new FormField(
                content: $content,
                contentLength: $contentLength,
                contentType: $contentType,
                filename: $filename,
            );
        }
    }

    /**
     * 使用 http_build_query 处理普通字段（包括多维数组）
     */
    private function processRegularFields(array $fields): void
    {
        // 使用 http_build_query 将数组转换为查询字符串格式
        $queryString = http_build_query($fields);
        
        // 通过 & 分割查询字符串，然后解析每个键值对
        $pairs = explode('&', $queryString);
        
        foreach ($pairs as $pair) {
            if (empty($pair)) continue;
            
            // 通过 = 分割键值对
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $key = urldecode($parts[0]);
                $value = urldecode($parts[1]);
                
                // 添加为 FormField
                $this->fields[$key] = new FormField(
                    content: $value,
                );
            }
        }
    }

    public function addFile(string $name, string $path, ?string $contentType = null, ?string $filename = null, int $bucketSize = 1024 * 1024 * 1024, int $tokensPerInterval = 1024 * 1024 * 1024, int $startPosition = 0, int $readLength = -1, int $chunkSizeKB = 1024): void
    {
        $this->fields[$name] = new FormFile(
            path: $path,
            filename: $filename,
            contentType: $contentType,
            bucketSize: $bucketSize,
            tokensPerInterval: $tokensPerInterval,
            startPosition: $startPosition,
            readLength: $readLength,
            chunkSizeKB: $chunkSizeKB,
        );
    }

    public function addMultiFile(string $name, array $paths, ?string $contentType = null, int $bucketSize = 1024 * 1024 * 1024, int $tokensPerInterval = 1024 * 1024 * 1024, int $startPosition = 0, int $readLength = -1, int $chunkSizeKB = 1024): void
    {
        $this->fields[$name] = new MultiFormFile(paths: $paths, contentType: $contentType, bucketSize: $bucketSize, tokensPerInterval: $tokensPerInterval, startPosition: $startPosition, readLength: $readLength, chunkSizeKB: $chunkSizeKB);
    }

    public function getHeaders(): array
    {
        return [
            'Content-Type'   => "multipart/form-data; boundary={$this->boundary}",
        ];
    }

    public function getBody(): ReadableStreamInterface
    {
        $stream = new ThroughStream();

        \React\EventLoop\Loop::futureTick(async(function () use ($stream) {
            try {
                foreach ($this->fields as $name => $field) {
                    $name = (string)$name; // 确保 $name 是字符串类型
                    if ($field instanceof FormField) {
                        $body = <<<BODY
--{$this->boundary}\r
Content-Disposition: form-data; name="{$name}"{$field->bodyContent}\r

BODY;
                        $stream->write($body);
                    } elseif ($field instanceof FormFile) {
                        await($this->writeFormFile($stream, $field, $name));
                    } elseif ($field instanceof MultiFormFile) {
                        // 处理多文件上传，使用内部的 FormFile 实例
                        $formFiles = $field->getFormFiles();
                        
                        foreach ($formFiles as $formFile) {
                            await($this->writeFormFile($stream, $formFile, "{$name}[]"));
                        }
                    }
                }

                $body = "--{$this->boundary}--\r\n";
                $stream->end($body);
            } catch (\Throwable $e) {
                $stream->emit('error', [$e]);
            }
        }));

        return $stream;
    }

    /**
     * 写入单个 FormFile 到流中的公用方法
     * @param ThroughStream $stream
     * @param FormFile $formFile
     * @param string $fieldName
     * @return \React\Promise\PromiseInterface
     */
    private function writeFormFile(ThroughStream $stream, FormFile $formFile, string $fieldName)
    {
        $deferred = new Deferred();
        
        $body = <<<BODY
--{$this->boundary}\r
Content-Disposition: form-data; name="{$fieldName}"
BODY;
        $body .= $formFile->getHeaders();
        $stream->write($body);
        
        $fileStream = $formFile->getBody();
        $fileStream->on('data', function ($data) use ($stream) {
            $stream->write($data);
        });
        $fileStream->on('end', function () use ($deferred, $stream) {
            $stream->write("\r\n");
            $deferred->resolve(null);
        });
        $fileStream->on('error', function ($error) use ($stream, $deferred) {
            $stream->emit('error', [$error]);
            $deferred->reject($error);
        });

        return $deferred->promise();
    }
} 