<?php

declare(strict_types=1);

namespace ReactphpX\MultipartFormData;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser as ReactBrowser;
use React\Promise\PromiseInterface;

class Browser extends ReactBrowser
{
    /**
     * Sends an HTTP Multipart Form Data POST request
     *
     * @param string $url
     * @param MultipartFormData $formData
     * @param array<string, string> $headers
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function postFormData(
        string $url,
        MultipartFormData $formData,
        array $headers = []
    ) {
        return $this->post(
            url: $url,
            headers: array_merge(
                $headers,
                $formData->getHeaders(),
            ),
            body: $formData->getBody()
        );
    }
} 