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

        foreach ($fields as $name => $field) {
            if ($field instanceof FormField || $field instanceof FormFile || $field instanceof MultiFormFile) {
                $this->fields[$name] = $field;
            } 
            // elseif (is_file($field)) {
            //     $this->addFile($name, $field);
            // } 
            else {
                $this->addField($name, $field);
            }
        }
    }

    public function addField(
        string $name,
        string $content,
        ?int $contentLength = null,
        ?string $contentType = null,
        ?string $filename = null,
    ): void {
        $this->fields[$name] = new FormField(
            content: $content,
            contentLength: $contentLength,
            contentType: $contentType,
            filename: $filename,
        );
    }

    public function addFile(string $name, string $path, ?string $contentType = null, ?string $filename = null, int $bucketSize = 1024 * 1024 * 1024, int $tokensPerInterval = 1024 * 1024 * 1024, int $p = 0, int $length = -1): void
    {
        $this->fields[$name] = new FormFile(
            path: $path,
            filename: $filename,
            contentType: $contentType,
            bucketSize: $bucketSize,
            tokensPerInterval: $tokensPerInterval,
            p: $p,
            length: $length,
        );
    }

    public function addMultiFile(string $name, array $paths, ?string $contentType = null, int $bucketSize = 1024 * 1024 * 1024, int $tokensPerInterval = 1024 * 1024 * 1024, int $p = 0, int $length = -1): void
    {
        $this->fields[$name] = new MultiFormFile(
            paths: $paths,
            contentType: $contentType,
            bucketSize: $bucketSize,
            tokensPerInterval: $tokensPerInterval,
            p: $p,
            length: $length,
        );
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

        $stream->on('data', function ($data) {
            echo $data;
        });
        
        \React\EventLoop\Loop::futureTick(async(function () use ($stream) {
            try {
                foreach ($this->fields as $name => $field) {
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