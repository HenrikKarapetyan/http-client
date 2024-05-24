<?php

declare(strict_types=1);

namespace Henrik\HttpClient;

use Fig\Http\Message\StatusCodeInterface;
use Henrik\HttpClient\Trait\ResponseTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use ResponseTrait;

    /**
     * @param int                         $statusCode
     * @param string[]                    $headers
     * @param string|StreamInterface|null $body
     * @param string                      $protocol
     * @param string                      $reasonPhrase
     */
    public function __construct(
        int $statusCode = StatusCodeInterface::STATUS_OK,
        array $headers = [],
        null|StreamInterface|string $body = null,
        string $protocol = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->init($statusCode, $reasonPhrase, $headers, $body, $protocol);
    }
}