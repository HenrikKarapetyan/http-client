<?php

declare(strict_types=1);

namespace Henrik\HttpClient\Factory;

use Henrik\HttpClient\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($serverParams, [], [], [], null, $method, $uri, [], null);
    }
}