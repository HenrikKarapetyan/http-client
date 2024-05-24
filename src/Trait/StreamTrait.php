<?php

declare(strict_types=1);

namespace Henrik\HttpClient\Trait;

use InvalidArgumentException;
use RuntimeException;

use const SEEK_SET;

use Throwable;

trait StreamTrait
{
    /**
     * @var resource|null
     */
    private $resource;

    /**
     * @var int|null
     */
    private ?int $size = null;

    /**
     * @var bool|null
     */
    private ?bool $seekable = null;

    /**
     * @var bool|null
     */
    private ?bool $writable = null;

    /**
     * @var bool|null
     */
    private ?bool $readable = null;

    /**
     * Closes the stream and any underlying resources when the instance is destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @throws Throwable
     * @throws RuntimeException
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->resource) {
            $resource = $this->detach();

            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource       = $this->resource;
        $this->resource = $this->size = null;
        $this->seekable = $this->writable = $this->readable = false;

        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null returns the size in bytes if known, or null if unknown
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = fstat($this->resource);

        return $this->size = isset($stats['size']) ? (int) $stats['size'] : null;
    }

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @throws RuntimeException on error
     *
     * @return int Position of the file pointer
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot tell position');
        }

        $result = ftell($this->resource);

        if ($result === false) {
            throw new RuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return !$this->resource || feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }

        return $this->seekable = ($this->resource && $this->getMetadata('seekable'));
    }

    /**
     * Seek to a position in the stream.
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical to the built-in
     *                    PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *                    offset bytes SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws RuntimeException on failure
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot seek position.');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            throw new RuntimeException('Error seeking within stream.');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws RuntimeException on failure
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     * @see seek()
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Returns or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        if ($this->writable !== null) {
            return $this->writable;
        }

        $mode = $this->getMetadata('mode');

        if (!is_string($mode)) {
            return $this->writable = false;
        }

        return $this->writable = (
            str_contains($mode, 'w')
            || str_contains($mode, '+')
            || str_contains($mode, 'x')
            || str_contains($mode, 'c')
            || str_contains($mode, 'a')
        );
    }

    /**
     * Write data to the stream.
     *
     * @param string $line the string that is to be written
     *
     * @throws RuntimeException on failure
     *
     * @return int returns the number of bytes written to the stream
     */
    public function write(string $line): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot write.');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $this->size = null;
        $result     = fwrite($this->resource, $line);

        if ($result === false) {
            throw new RuntimeException('Error writing to stream.');
        }

        return $result;
    }

    /**
     * Returns or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        if ($this->readable !== null) {
            return $this->readable;
        }
        $mode = $this->getMetadata('mode');

        if (!is_string($mode)) {
            return $this->readable = false;
        }

        return $this->readable = (str_contains($mode, 'r') || str_contains($mode, '+'));
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if underlying stream
     *                    call returns fewer bytes.
     *
     * @throws RuntimeException if an error occurs
     *
     * @return string returns the data read from the stream, or an empty string
     *                if no bytes are available
     */
    public function read(int $length): string
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot read.');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }
        $result = fread($this->resource, max(1, $length));

        if ($result === false) {
            throw new RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * Returns the remaining contents in a string.
     *
     * @throws RuntimeException if unable to read or an error occurs while reading
     * @throws Throwable
     *
     * @return string
     */
    public function getContents(): string
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot read.');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $exception = null;
        $message   = 'Unable to read stream contents';

        set_error_handler(static function (int $errno, string $errstr) use (&$exception, $message) {
            throw $exception = new RuntimeException("{$errno}->{$message} : {$errstr}");
        });

        try {
            $contents = stream_get_contents($this->resource);
            if (!$contents) {
                throw new RuntimeException($message);
            }

            return $contents;
        } catch (Throwable $e) {
            throw $e === $exception ? $e : new RuntimeException("{$message}: {$e->getMessage()}", 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string|null $key specific metadata to retrieve
     *
     * @return array|mixed|null Returns an associative array if no key is
     *                          provided. Returns a specific key value if a key is provided and the
     *                          value is found, or null if the key is not found.
     */
    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->resource)) {
            return $key ? null : [];
        }

        $metadata = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * Initialization the stream resource.
     *
     * Called when creating `Psr\Http\Message\StreamInterface` instance.
     *
     * @param string|resource|null $stream string stream target or stream resource
     * @param string               $mode   resource mode for stream target
     *
     * @throws InvalidArgumentException if the stream or resource is invalid
     * @throws RuntimeException         if the stream or file cannot be opened
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD)
     */
    private function init(mixed $stream, string $mode): void
    {
        if (is_string($stream)) {
            $stream = $stream === '' ? false : @fopen($stream, $mode);

            if ($stream === false) {
                throw new RuntimeException('The stream or file cannot be opened.');
            }
        }

        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException(
                'Invalid stream provided. It must be a string stream identifier or stream resource.',
            );
        }

        $this->resource = $stream;
    }
}