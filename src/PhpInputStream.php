<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/9/8  10:52:0.
 */

namespace henrik\http-client;


/**
 * Caching version of php://input
 */
class PhpInputStream extends Stream
{
    /**
     * @var string
     */
    private $cache = '';

    /**
     * @var bool
     */
    private $reachedEof = false;

    /**
     * @param string|resource $stream
     * @param string $mode
     */
    public function __construct($stream = 'php://input', $mode = 'r')
    {
        $mode = 'r';
        parent::__construct($stream, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if ($this->reachedEof) {
            return $this->cache;
        }

        $this->getContents();
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $content = parent::read($length);
        if ($content && !$this->reachedEof) {
            $this->cache .= $content;
        }

        if ($this->eof()) {
            $this->reachedEof = true;
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents($maxLength = -1)
    {
        if ($this->reachedEof) {
            return $this->cache;
        }

        $contents = stream_get_contents($this->resource, $maxLength);
        $this->cache .= $contents;

        if ($maxLength === -1 || $this->eof()) {
            $this->reachedEof = true;
        }

        return $contents;
    }
}
