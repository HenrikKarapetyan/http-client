<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:36:25.
 */

namespace henrik\http_client;


use henrik\http_client\exceptions\InvalidArgumentsException;
use henrik\http_client\exceptions\StreamException;
use henrik\http_client\exceptions\UploadedFileException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class UploadedFile
 * @package henrik\http_client
 */
class UploadedFile implements UploadedFileInterface
{

    /**
     * @var string
     */
    private $clientFilename;

    /**
     * @var string
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var null|string
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int
     */
    private $size;

    /**
     * @var null|StreamInterface
     */
    private $stream;

    /**
     * UploadedFile constructor.
     * @param $streamOrFile
     * @param $size
     * @param $errorStatus
     * @param null $clientFilename
     * @param null $clientMediaType
     */
    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        }
        if (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        }

        if (!$this->file && !$this->stream) {
            if (!$streamOrFile instanceof StreamInterface) {
                throw new InvalidArgumentsException('Invalid stream or file provided for UploadedFile');
            }
            $this->stream = $streamOrFile;
        }

        if (!is_int($size)) {
            throw new InvalidArgumentsException('Invalid size provided for UploadedFile; must be an int');
        }
        $this->size = $size;

        if (!is_int($errorStatus)
            || 0 > $errorStatus
            || 8 < $errorStatus
        ) {
            throw new InvalidArgumentsException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant'
            );
        }
        $this->error = $errorStatus;

        if (null !== $clientFilename && !is_string($clientFilename)) {
            throw new InvalidArgumentsException(
                'Invalid client filename provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientFilename = $clientFilename;

        if (null !== $clientMediaType && !is_string($clientMediaType)) {
            throw new InvalidArgumentsException(
                'Invalid client media type provided for UploadedFile; must be null or a string'
            );
        }
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new StreamException('Cannot retrieve stream after it has already been moved');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        if (!is_string($targetPath)) {
            throw new InvalidArgumentsException(
                'Invalid path provided for move operation; must be a string'
            );
        }

        if (empty($targetPath)) {
            throw new InvalidArgumentsException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->moved) {
            throw new UploadedFileException('Cannot move file; already moved!');
        }

        $sapi = PHP_SAPI;
        switch (true) {
            case (empty($sapi) || 0 === strpos($sapi, 'cli') || !$this->file):
                // Non-SAPI environment, or no filename present
                $this->writeFile($targetPath);
                break;
            default:
                // SAPI environment, with file present
                if (false === move_uploaded_file($this->file, $targetPath)) {
                    throw new UploadedFileException('Error occurred while moving uploaded file');
                }
                break;
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    private function writeFile($path)
    {
        $handle = fopen($path, 'wb+');
        if (false === $handle) {
            throw new UploadedFileException('Unable to write to designated path');
        }

        $this->stream->rewind();
        while (!$this->stream->eof()) {
            fwrite($handle, $this->stream->read(4096));
        }

        fclose($handle);
    }
}