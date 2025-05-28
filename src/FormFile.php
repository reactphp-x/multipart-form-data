<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use React\Stream\ReadableStreamInterface;
use ReactphpX\Bandwidth\Bandwidth;

class FormFile
{
    public function __construct(
        private string $path,
        private ?string $filename = null,
        private ?string $contentType = null,
        // 10M/sec burst rate
        private int $bucketSize = 1024 * 1024 * 1024,
        // 1M/sec sustained rate
        private int $tokensPerInterval = 1024 * 1024 * 1024,
        private int $p = 0,
        private int $length = -1
    )
    {
        if (!is_file($path)) {
            throw new \RuntimeException('File not found');
        }
    }

    public function getHeaders(): string
    {
        $data = '';

        $filename = $this->filename ?? \basename($this->path);
        $data .= "; filename=\"{$filename}\"\r\n";

        $contentType = $this->contentType ?? (\mime_content_type($this->path) ?: null) ?? 'application/octet-stream';
        $data .= "Content-Type: {$contentType}\r\n";

        $data .= "Content-Length: " . filesize($this->path) . "\r\n";
        
        $data .= "\r\n";

        return $data;
    }

    public function getBody(): ReadableStreamInterface
    {
        return (new Bandwidth($this->bucketSize, $this->tokensPerInterval, $this->p, $this->length))->file($this->path);
    }
} 