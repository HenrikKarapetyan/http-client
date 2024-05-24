<?php

declare(strict_types=1);

namespace Henrik\HttpClient;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface
{
    /**
     * @const array
     *
     * @see https://www.php.net/manual/en/features.file-upload.errors.php
     */
    private const ERRORS = [
        UPLOAD_ERR_OK        => 'There is no error, the file uploaded with success.',
        UPLOAD_ERR_INI_SIZE  => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive'
            . ' that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @var string|null
     */
    private ?string $file = null;

    /**
     * @var int
     */
    private int $size;

    /**
     * @var int
     */
    private int $error;

    /**
     * @var string|null
     */
    private ?string $clientFilename;

    /**
     * @var string|null
     */
    private ?string $clientMediaType;

    /**
     * @var bool
     */
    private bool $isMoved = false;

    /**
     * @param string|StreamInterface|resource $streamOrFile
     * @param int                             $size
     * @param int                             $error
     * @param string|null                     $clientFilename
     * @param string|null                     $clientMediaType
     */
    public function __construct(
        $streamOrFile,
        int $size,
        int $error,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        $this->checkErrors($error);

        $this->size            = $size;
        $this->error           = $error;
        $this->clientFilename  = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($error !== UPLOAD_ERR_OK) {
            return;
        }
        $this->init($streamOrFile);
    }

    /**
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::ERRORS[$this->error]);
        }

        if ($this->isMoved) {
            throw new RuntimeException('The stream is not available because it has been moved.');
        }

        if ($this->stream === null) {
            $this->stream = new Stream($this->file, 'r+');
        }

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::ERRORS[$this->error]);
        }

        if ($this->isMoved) {
            throw new RuntimeException('The file cannot be moved because it has already been moved.');
        }

        if (empty($targetPath)) {
            throw new InvalidArgumentException('Target path is not valid for move. It must be a non-empty string.');
        }

        $targetDirectory = dirname($targetPath);

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new RuntimeException(sprintf(
                'The target directory "%s" does not exist or is not writable.',
                $targetDirectory
            ));
        }

        $this->moveOrWriteFile($targetPath);
        $this->isMoved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Moves if used in an SAPI environment where $_FILES is populated, when writing
     * files via is_uploaded_file() and move_uploaded_file() or writes If SAPI is not used.
     *
     * @param string $targetPath
     */
    private function moveOrWriteFile(string $targetPath): void
    {
        if ($this->file !== null) {
            $isCliEnv = (!PHP_SAPI || str_starts_with(PHP_SAPI, 'cli') || str_starts_with(PHP_SAPI, 'phpdbg'));

            if (!($isCliEnv ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath))) {
                throw new RuntimeException(sprintf('Uploaded file could not be moved to "%s".', $targetPath));
            }

            return;
        }
        $file = fopen($targetPath, 'wb+');
        if (!$file) {
            throw new RuntimeException(sprintf('Unable to write to "%s".', $targetPath));
        }

        $this->stream->rewind();

        while (!$this->stream->eof()) {
            fwrite($file, $this->stream->read(512000));
        }

        fclose($file);
    }

    private function checkErrors(int $error): void
    {
        if (!array_key_exists($error, self::ERRORS)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid error status for "UploadedFile". It must be one of "UPLOAD_ERR_*" constants:  "%s".',
                $error,
                implode('", "', array_keys(self::ERRORS))
            ));
        }

    }

    /**
     * @param string|StreamInterface|resource $streamOrFile
     *
     * @return void
     */
    private function init($streamOrFile): void
    {
        switch ($streamOrFile) {
            case is_string($streamOrFile):
                $this->file = $streamOrFile;

                break;
            case is_resource($streamOrFile):
                $this->stream = new Stream($streamOrFile);

                break;
            case $streamOrFile instanceof StreamInterface:
                $this->stream = $streamOrFile;

                break;

            default: throw new InvalidArgumentException(sprintf(
                '"%s" is not valid stream or file provided for "UploadedFile".',
                is_object($streamOrFile) ? get_class($streamOrFile) : gettype($streamOrFile)
            ));

        }

    }
}