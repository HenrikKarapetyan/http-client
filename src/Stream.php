<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:35:49.
 */

namespace henrik\http_client;


use henrik\http_client\exceptions\InvalidArgumentsException;
use henrik\http_client\exceptions\StreamException;
use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 * @package henrik\http_client
 */
class Stream implements StreamInterface
{

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string|resource
     */
    protected $stream;

    /**
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @throws InvalidArgumentsException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->stream = $stream;

        if (is_resource($stream)) {
            $this->resource = $stream;
        } elseif (is_string($stream)) {
            set_error_handler(function ($errno, $errstr) {
                throw new InvalidArgumentsException(
                    'Invalid file provided for stream; must be a valid path with valid permissions'
                );
            }, E_WARNING);
            $this->resource = fopen($stream, $mode);
            restore_error_handler();
        } else {
            throw new InvalidArgumentsException(
                'Invalid stream provided; must be a string stream identifier or resource'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (StreamException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (!$this->resource) {
            return;
        }

        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Attach a new stream/resource to the instance.
     *
     * @param string|resource $resource
     * @param string $mode
     * @throws InvalidArgumentsException for stream identifier that cannot be
     *     cast to a resource
     * @throws InvalidArgumentsException for non-resource stream
     */
    public function attach($resource, $mode = 'r')
    {
        $error = null;
        if (!is_resource($resource) && is_string($resource)) {
            set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $resource = fopen($resource, $mode);
            restore_error_handler();
        }

        if ($error) {
            throw new InvalidArgumentsException('Invalid stream reference provided');
        }

        if (!is_resource($resource)) {
            throw new InvalidArgumentsException(
                'Invalid stream provided; must be a string stream identifier or resource'
            );
        }

        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (!$this->resource) {
            throw new StreamException('No resource available; cannot tell position');
        }

        $result = ftell($this->resource);
        if (!is_int($result)) {
            throw new StreamException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (!$this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return $meta['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->resource) {
            throw new StreamException('No resource available; cannot seek position');
        }

        if (!$this->isSeekable()) {
            throw new StreamException('Stream is not seekable');
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new StreamException('Error seeking within stream');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return is_writable($meta['uri']);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (!$this->resource) {
            throw new StreamException('No resource available; cannot write');
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new StreamException('Error writing to stream');
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (!$this->resource) {
            throw new StreamException('No resource available; cannot read');
        }

        if (!$this->isReadable()) {
            throw new StreamException('Stream is not readable');
        }

        $result = fread($this->resource, $length);

        if (false === $result) {
            throw new StreamException('Error reading stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            return '';
        }

        $result = stream_get_contents($this->resource);
        if (false === $result) {
            throw new StreamException('Error reading from stream');
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (null === $key) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }
}