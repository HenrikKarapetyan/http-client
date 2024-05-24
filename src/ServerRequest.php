<?php

declare(strict_types=1);

namespace Henrik\HttpClient;

use Fig\Http\Message\RequestMethodInterface;
use Henrik\HttpClient\Trait\RequestTrait;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest implements ServerRequestInterface
{
    use RequestTrait;

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @var array<int|string, mixed>
     */
    private array $cookieParams;

    /**
     * @var array<int|string, mixed>|object|null
     */
    private null|array|object $parsedBody;

    /**
     * @var array<int|string, mixed>
     */
    private array $queryParams;

    /**
     * @var array<int|string, mixed>
     */
    private array $serverParams;

    /**
     * @var array<string, array<UploadedFileInterface>|UploadedFileInterface|mixed>
     */
    private array $uploadedFiles;

    /**
     * @param array<int|string, mixed>                                                $serverParams
     * @param array<string, array<UploadedFileInterface>|UploadedFileInterface|mixed> $uploadedFiles
     * @param array<int|string, mixed>                                                $cookieParams
     * @param array<int|string, mixed>                                                $queryParams
     * @param object|array<int|string, mixed>|null                                    $parsedBody
     * @param string                                                                  $method
     * @param string|UriInterface                                                     $uri
     * @param string[]                                                                $headers
     * @param string|StreamInterface|null                                             $body
     * @param string                                                                  $protocol
     *
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        array $cookieParams = [],
        array $queryParams = [],
        null|array|object $parsedBody = null,
        string $method = RequestMethodInterface::METHOD_GET,
        string|UriInterface $uri = '',
        array $headers = [],
        null|StreamInterface|string $body = null,
        string $protocol = '1.1'
    ) {
        $this->validateUploadedFiles($uploadedFiles);
        $this->uploadedFiles = $uploadedFiles;
        $this->serverParams  = $serverParams;
        $this->cookieParams  = $cookieParams;
        $this->queryParams   = $queryParams;
        $this->parsedBody    = $parsedBody;
        $this->init($method, $uri, $headers, $body, $protocol);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $cookies
     *
     * @return ServerRequestInterface
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new               = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed> $cookies
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<int|string, mixed> $query
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new              = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, array<UploadedFileInterface>|UploadedFileInterface|mixed>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array<string, array<UploadedFileInterface>|UploadedFileInterface|mixed> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed>|object|null
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $data
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_array($data) && !is_object($data) && $data !== null) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid Parsed Body. It must be a null, an array, or an object.',
                gettype($data)
            ));
        }

        $new             = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        if (array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return $this;
        }

        $new                    = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * @param array<string, array<UploadedFileInterface>|UploadedFileInterface|mixed> $uploadedFiles
     *
     * @throws InvalidArgumentException
     */
    private function validateUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);

                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid item in uploaded files structure.'
                    . '"%s" is not an instance of "\Psr\Http\Message\UploadedFileInterface".',
                    is_object($file) ? get_class($file) : gettype($file)
                ));
            }
        }
    }
}