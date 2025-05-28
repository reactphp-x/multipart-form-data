<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use ReactphpX\Bandwidth\Bandwidth;

class MultiFormFile
{
    /** @var FormFile[] */
    private array $formFiles = [];
    
    public function __construct(
        array $paths,
        ?string $contentType = null,
        // 10M/sec burst rate
        int $bucketSize = 1024 * 1024 * 1024,
        // 1M/sec sustained rate
        int $tokensPerInterval = 1024 * 1024 * 1024,
        int $p = 0,
        int $length = -1
    )
    {
        if (empty($paths)) {
            throw new \RuntimeException('At least one file path must be provided');
        }

        // 为每个文件路径创建 FormFile 实例
        foreach ($paths as $path) {
            $this->formFiles[] = new FormFile(
                path: $path,
                filename: null, // 使用默认文件名
                contentType: $contentType,
                bucketSize: $bucketSize,
                tokensPerInterval: $tokensPerInterval,
                p: $p,
                length: $length
            );
        }
    }

    /**
     * 获取所有 FormFile 实例
     */
    public function getFormFiles(): array
    {
        return $this->formFiles;
    }

    /**
     * 获取文件数量
     */
    public function getFileCount(): int
    {
        return count($this->formFiles);
    }

    /**
     * 获取所有文件的总大小
     */
    public function getTotalSize(): int
    {
        $totalSize = 0;
        foreach ($this->formFiles as $formFile) {
            // 从 headers 中解析 Content-Length
            $headers = $formFile->getHeaders();
            if (preg_match('/Content-Length: (\d+)/', $headers, $matches)) {
                $totalSize += (int)$matches[1];
            }
        }
        return $totalSize;
    }

    /**
     * 获取文件路径数组（用于兼容性）
     */
    public function getPaths(): array
    {
        $paths = [];
        foreach ($this->formFiles as $formFile) {
            // 由于 FormFile 的 path 是私有的，我们需要从 headers 中获取文件名
            $headers = $formFile->getHeaders();
            if (preg_match('/filename="([^"]+)"/', $headers, $matches)) {
                $paths[] = $matches[1]; // 这里只能返回文件名，不是完整路径
            }
        }
        return $paths;
    }

    /**
     * 获取指定索引的 FormFile
     */
    public function getFormFile(int $index): FormFile
    {
        if (!isset($this->formFiles[$index])) {
            throw new \InvalidArgumentException("File index {$index} does not exist");
        }
        return $this->formFiles[$index];
    }
} 