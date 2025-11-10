<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use React\Stream\ReadableStreamInterface;
use ReactphpX\Bandwidth\Bandwidth;

/**
 * 表示单个文件上传，支持文件流和带宽控制
 */
class FormFile
{
    /**
     * @param string $path 文件路径
     * @param string|null $filename 自定义文件名（可选）
     * @param string|null $contentType 自定义内容类型（可选）
     * @param int $bucketSize 令牌桶大小（字节），控制突发传输速率
     * @param int $tokensPerInterval 每间隔的令牌数（字节/秒），控制持续传输速率
     * @param int $startPosition 文件读取起始位置（字节）
     * @param int $readLength 读取长度（字节，-1 表示读取到文件末尾）
     * @param int $chunkSizeKB 每次读取的数据块大小（KB）
     */
    public function __construct(
        private string $path,
        private ?string $filename = null,
        private ?string $contentType = null,
        // 默认突发速率
        private int $bucketSize = 1024 * 1024 * 10,
        // 默认持续速率  
        private int $tokensPerInterval = 1024 * 1024 * 1,
        private int $startPosition = 0,
        private int $readLength = -1,
        private int $chunkSizeKB = 1024
    )
    {
        $this->validateFile();
        $this->validateParameters();
    }
    
    /**
     * 创建带自定义带宽限制的文件上传
     * 
     * @param string $path 文件路径
     * @param int $maxBytesPerSecond 最大上传速度（字节/秒）
     * @param int $chunkSizeKB 数据块大小（KB）
     * @param string|null $filename 自定义文件名
     * @return self
     */
    public static function withBandwidthLimit(
        string $path, 
        int $maxBytesPerSecond = 1024 * 1024, // 1MB/s 默认
        int $chunkSizeKB = 1024, // 1MB 块大小
        ?string $filename = null
    ): self {
        return new self(
            path: $path,
            filename: $filename,
            bucketSize: $maxBytesPerSecond * 2, // 允许 2 秒的突发
            tokensPerInterval: $maxBytesPerSecond,
            chunkSizeKB: $chunkSizeKB
        );
    }
    
    /**
     * 创建文件的部分上传
     * 
     * @param string $path 文件路径
     * @param int $startPosition 起始位置（字节）
     * @param int $length 读取长度（字节）
     * @param int $chunkSizeKB 数据块大小（KB）
     * @return self
     */
    public static function partial(
        string $path,
        int $startPosition,
        int $length,
        int $chunkSizeKB = 1024
    ): self {
        return new self(
            path: $path,
            startPosition: $startPosition,
            readLength: $length,
            chunkSizeKB: $chunkSizeKB
        );
    }

    /**
     * 验证文件
     */
    private function validateFile(): void
    {
        if (!is_file($this->path)) {
            throw new \RuntimeException("File not found: {$this->path}");
        }
        
        if (!is_readable($this->path)) {
            throw new \RuntimeException("File is not readable: {$this->path}");
        }
    }
    
    /**
     * 验证参数
     */
    private function validateParameters(): void
    {
        if ($this->bucketSize < 0) {
            throw new \InvalidArgumentException('Bucket size must be non-negative');
        }
        
        if ($this->tokensPerInterval < 0) {
            throw new \InvalidArgumentException('Tokens per interval must be non-negative');
        }
        
        if ($this->startPosition < 0) {
            throw new \InvalidArgumentException('Start position must be non-negative');
        }
        
        if ($this->chunkSizeKB <= 0) {
            throw new \InvalidArgumentException('Chunk size must be positive');
        }
        
        $fileSize = filesize($this->path);
        if ($this->startPosition >= $fileSize) {
            throw new \InvalidArgumentException('Start position exceeds file size');
        }
        
        if ($this->readLength > 0 && ($this->startPosition + $this->readLength) > $fileSize) {
            throw new \InvalidArgumentException('Read length exceeds file size');
        }
    }

    /**
     * 获取文件大小信息
     * 
     * @return array{total: int, readable: int}
     */
    public function getSizeInfo(): array
    {
        $totalSize = filesize($this->path);
        $readableSize = $this->readLength > 0 
            ? min($this->readLength, $totalSize - $this->startPosition)
            : $totalSize - $this->startPosition;
            
        return [
            'total' => $totalSize,
            'readable' => $readableSize
        ];
    }
    
    /**
     * 获取带宽配置信息
     * 
     * @return array{bucketSize: int, tokensPerInterval: int, maxSpeedMBps: float, chunkSizeKB: int}
     */
    public function getBandwidthInfo(): array
    {
        return [
            'bucketSize' => $this->bucketSize,
            'tokensPerInterval' => $this->tokensPerInterval,
            'maxSpeedMBps' => round($this->tokensPerInterval / 1024 / 1024, 2),
            'chunkSizeKB' => $this->chunkSizeKB
        ];
    }

    public function getHeaders(): string
    {
        $data = '';

        $filename = $this->filename ?? \basename($this->path);
        $data .= "; filename=\"{$filename}\"\r\n";

        $contentType = $this->contentType ?? (\mime_content_type($this->path) ?: null) ?? 'application/octet-stream';
        $data .= "Content-Type: {$contentType}\r\n";

        // 使用实际要传输的大小，而不是整个文件大小
        $sizeInfo = $this->getSizeInfo();
        $data .= "Content-Length: " . $sizeInfo['readable'] . "\r\n";
        
        $data .= "\r\n";

        return $data;
    }

    public function getBody(): ReadableStreamInterface
    {
        return (new Bandwidth(
            bucketSize: $this->bucketSize, 
            tokensPerInterval: $this->tokensPerInterval
        ))->file(
            path: $this->path, 
            p: $this->startPosition, 
            length: $this->readLength, 
            readKB: $this->chunkSizeKB
        );
    }
} 
