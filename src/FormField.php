<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

readonly class FormField
{
    public string $bodyContent;

    public function __construct(
        string $content,
        ?int $contentLength = null,
        ?string $contentType = null,
        ?string $filename = null,
    )
    {
        $data = '';

        if ($filename !== null) {
            $data .= "; filename=\"{$filename}\"";
        }

        $data .= "\r\n";

        if ($contentType !== null) {
            $data .= "Content-Type: {$contentType}\r\n";
        }

        if ($contentLength !== null) {
            $data .= "Content-Length: {$contentLength}\r\n";
        }

        $data .= "\r\n";
        $data .= $content;

        $this->bodyContent = $data;
    }
} 