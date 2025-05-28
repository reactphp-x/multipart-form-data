<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use ReactphpX\Bandwidth\Bandwidth;

/**
 * 表示多个文件上传，支持统一的带宽控制和配置
 */
class MultiFormFile
{
    /** @var FormFile[] */
    private array $formFiles = [];
    
    /**
     * @param array $paths 文件路径数组
     * @param string|null $contentType 统一的内容类型（可选）
     * @param int $bucketSize 令牌桶大小（字节），控制突发传输速率
     * @param int $tokensPerInterval 每间隔的令牌数（字节/秒），控制持续传输速率
     * @param int $startPosition 文件读取起始位置（字节）
     * @param int $readLength 读取长度（字节，-1 表示读取到文件末尾）
     * @param int $chunkSizeKB 每次读取的数据块大小（KB）
     */
    public function __construct(
        array $paths,
        ?string $contentType = null,
        // 默认突发速率
        int $bucketSize = 1024 * 1024 * 1024 * 10,
        // 默认持续速率
        int $tokensPerInterval = 1024 * 1024 * 1024 * 10,
        int $startPosition = 0,
        int $readLength = -1,
        int $chunkSizeKB = 1024
    )
    {
        if (empty($paths)) {
            throw new \RuntimeException('At least one file path must be provided');
        }

        // 为每个文件路径创建 FormFile 实例
        foreach ($paths as $path) {
            if ($path instanceof FormFile) {
                $this->formFiles[] = $path;
                continue;
            }
            $this->formFiles[] = new FormFile(
                path: $path,
                filename: null, // 使用默认文件名
                contentType: $contentType,
                bucketSize: $bucketSize,
                tokensPerInterval: $tokensPerInterval,
                startPosition: $startPosition,
                readLength: $readLength,
                chunkSizeKB: $chunkSizeKB
            );
        }
    }

    /**
     * 创建带自定义带宽限制的多文件上传
     * 
     * @param array $paths 文件路径数组
     * @param int $maxBytesPerSecond 最大上传速度（字节/秒）
     * @param int $chunkSizeKB 数据块大小（KB）
     * @param string|null $contentType 统一的内容类型
     * @return self
     */
    public static function withBandwidthLimit(
        array $paths, 
        int $maxBytesPerSecond = 1024 * 1024 * 1024, // 1MB/s 默认
        int $chunkSizeKB = 1024, // 1MB 块大小
        ?string $contentType = null
    ): self {
        return new self(
            paths: $paths,
            contentType: $contentType,
            bucketSize: $maxBytesPerSecond * 2, // 允许 2 秒的突发
            tokensPerInterval: $maxBytesPerSecond,
            chunkSizeKB: $chunkSizeKB
        );
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
     * 获取所有文件的总大小信息
     * 
     * @return array{totalFiles: int, totalSize: int, averageSize: int, files: array}
     */
    public function getSizeInfo(): array
    {
        $totalSize = 0;
        $filesInfo = [];
        
        foreach ($this->formFiles as $index => $formFile) {
            $sizeInfo = $formFile->getSizeInfo();
            $filesInfo[] = [
                'index' => $index,
                'size' => $sizeInfo['readable'],
                'totalSize' => $sizeInfo['total']
            ];
            $totalSize += $sizeInfo['readable'];
        }
        
        return [
            'totalFiles' => count($this->formFiles),
            'totalSize' => $totalSize,
            'averageSize' => count($this->formFiles) > 0 ? (int)($totalSize / count($this->formFiles)) : 0,
            'files' => $filesInfo
        ];
    }

    /**
     * 获取带宽配置信息
     * 
     * @return array{bucketSize: int, tokensPerInterval: int, maxSpeedMBps: float, chunkSizeKB: int, totalFiles: int}
     */
    public function getBandwidthInfo(): array
    {
        // 获取第一个文件的带宽信息（所有文件都使用相同配置）
        if (empty($this->formFiles)) {
            return [
                'bucketSize' => 0,
                'tokensPerInterval' => 0,
                'maxSpeedMBps' => 0.0,
                'chunkSizeKB' => 0,
                'totalFiles' => 0
            ];
        }
        
        $bandwidthInfo = $this->formFiles[0]->getBandwidthInfo();
        $bandwidthInfo['totalFiles'] = count($this->formFiles);
        
        return $bandwidthInfo;
    }

    /**
     * 获取文件路径数组（用于兼容性）
     * 注意：这个方法现在返回文件名而不是完整路径，因为路径是私有的
     */
    public function getPaths(): array
    {
        $paths = [];
        foreach ($this->formFiles as $formFile) {
            // 从 headers 中获取文件名
            $headers = $formFile->getHeaders();
            if (preg_match('/filename="([^"]+)"/', $headers, $matches)) {
                $paths[] = $matches[1];
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

    /**
     * 获取所有文件的详细信息
     * 
     * @return array
     */
    public function getFilesInfo(): array
    {
        $filesInfo = [];
        
        foreach ($this->formFiles as $index => $formFile) {
            $sizeInfo = $formFile->getSizeInfo();
            $bandwidthInfo = $formFile->getBandwidthInfo();
            
            // 从 headers 中提取文件信息
            $headers = $formFile->getHeaders();
            $filename = 'unknown';
            $contentType = 'application/octet-stream';
            
            if (preg_match('/filename="([^"]+)"/', $headers, $matches)) {
                $filename = $matches[1];
            }
            if (preg_match('/Content-Type: ([^\r\n]+)/', $headers, $matches)) {
                $contentType = trim($matches[1]);
            }
            
            $filesInfo[] = [
                'index' => $index,
                'filename' => $filename,
                'contentType' => $contentType,
                'size' => $sizeInfo['readable'],
                'totalSize' => $sizeInfo['total'],
                'maxSpeedMBps' => $bandwidthInfo['maxSpeedMBps'],
                'chunkSizeKB' => $bandwidthInfo['chunkSizeKB']
            ];
        }
        
        return $filesInfo;
    }
} 