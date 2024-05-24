<?php

declare(strict_types=1);

namespace Henrik\HttpClient;

use Henrik\HttpClient\Trait\StreamTrait;
use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    use StreamTrait;

    /**
     * @param string|resource|null $stream
     * @param string               $mode
     */
    public function __construct($stream = 'php://temp', string $mode = 'wb+')
    {
        $this->init($stream, $mode);
    }
}