<?php

declare(strict_types=1);

namespace Henrik\HttpClient;

use Fig\Http\Message\RequestMethodInterface;
use Henrik\HttpClient\Trait\RequestTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    use RequestTrait;

    /**
     * @param string                      $method
     * @param string|UriInterface         $uri
     * @param string[]                    $headers
     * @param string|StreamInterface|null $body
     * @param string                      $protocol
     */
    public function __construct(
        string $method = RequestMethodInterface::METHOD_GET,
        string|UriInterface $uri = '',
        array $headers = [],
        null|StreamInterface|string $body = null,
        string $protocol = '1.1'
    ) {
        $this->init($method, $uri, $headers, $body, $protocol);
    }
}