<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:19:50.
 */

namespace henrik\http_client;


use http\Exception\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Class Uri
 * @package henrik\http_client
 */
class Uri implements UriInterface
{
    /**
     * Sub-delimiters used in query strings and fragments.
     *
     * @const string
     */
    const CHAR_SUB_DELIMITERS = '!\$&\'\(\)\*\+,;=';
    /**
     * Unreserved characters used in paths, query strings, and fragments.
     *
     * @const string
     */
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * @var
     */
    private $scheme;
    /**
     * @var
     */
    private $host;
    /**
     * @var
     */
    private $port;
    /**
     * @var
     */
    private $path;
    /**
     * @var
     */
    private $fragment;
    /**
     * @var
     */
    private $query;
    /**
     * @var
     */
    private $uriString;
    /**
     * @var string
     */
    private $userInfo;
    /**
     * @var array
     */
    private $allowedSchemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * @param string $uri
     * @throws InvalidArgumentException on non-string $uri argument
     */
    public function __construct($uri = '')
    {
        if (!is_string($uri)) {
            throw new InvalidArgumentException(sprintf(
                'URI passed to constructor must be a string; received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            ));
        }
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (null !== $this->uriString) {
            return $this->uriString;
        }
        $this->uriString = $this->createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(), // Absolute URIs should use a "/" for an empty path
            $this->query,
            $this->fragment
        );
        return $this->uriString;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return empty($this->scheme) ? '' : $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }
        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }
        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        return !empty($this->userInfo) ? $this->userInfo : "";
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return !empty($this->host) ? $this->host : "";
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port)
            ? $this->port
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return !empty($this->path) ? $this->path : "";
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return !empty($this->query) ? $this->query : "";
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        return !empty($this->fragment) ? $this->fragment : "";
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        if ($scheme === $this->scheme) {
            // Do nothing if no change was made.
            return clone $this;
        }
        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if ($password) {
            $info .= ':' . $password;
        }
        if ($info === $this->userInfo) {
            // Do nothing if no change was made.
            return clone $this;
        }
        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        if ($host === $this->host) {
            // Do nothing if no change was made.
            return clone $this;
        }
        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        if (!(is_integer($port) || (is_string($port) && is_numeric($port)))) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%s" specified; must be an integer or integer string',
                (is_object($port) ? get_class($port) : gettype($port))
            ));
        }
        $port = (int)$port;
        if ($port === $this->port) {
            // Do nothing if no change was made.
            return clone $this;
        }
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%d" specified; must be a valid TCP/UDP port',
                $port
            ));
        }
        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }
        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }
        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }
        $path = $this->filterPath($path);
        if ($path === $this->path) {
            // Do nothing if no change was made.
            return clone $this;
        }
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        if (!is_string($query)) {
            throw new InvalidArgumentException(
                'Query string must be a string'
            );
        }
        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }
        $query = $this->filterQuery($query);
        if ($query === $this->query) {
            // Do nothing if no change was made.
            return clone $this;
        }
        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }


    /**
     * Parse a URI into its parts, and set the properties
     * @param $uri
     */
    private function parseUri($uri)
    {
        $parts = parse_url($uri);
        if (false === $parts) {
            throw new \InvalidArgumentException(
                'The source URI string appears to be malformed'
            );
        }
        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
        $this->host = isset($parts['host']) ? $parts['host'] : '';
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQuery($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterFragment($parts['fragment']) : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private function createUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';
        if (!empty($scheme)) {
            $uri .= sprintf('%s://', $scheme);
        }
        if (!empty($authority)) {
            $uri .= $authority;
        }
        if ($path) {
            if (empty($path) || '/' !== substr($path, 0, 1)) {
                $path = '/' . $path;
            }
            $uri .= $path;
        }
        if ($query) {
            $uri .= sprintf('?%s', $query);
        }
        if ($fragment) {
            $uri .= sprintf('#%s', $fragment);
        }
        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @return bool
     */
    private function isNonStandardPort($scheme, $host, $port)
    {
        if (!$scheme) {
            return true;
        }
        if (!$host || !$port) {
            return false;
        }
        return !isset($this->allowedSchemes[$scheme]) || $port !== $this->allowedSchemes[$scheme];
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * @param string $scheme Scheme name.
     *
     * @return string Filtered scheme.
     */
    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);
        if (empty($scheme)) {
            return '';
        }
        if (!array_key_exists($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be any empty string or in the set (%s)',
                $scheme,
                implode(', ', array_keys($this->allowedSchemes))
            ));
        }
        return $scheme;
    }

    /**
     * Filters the path of a URI to ensure it is properly encoded.
     *
     * @param string $path
     * @return string
     */
    private function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $path
        );
    }

    /**
     * Filter a query string to ensure it is propertly encoded.
     *
     * Ensures that the values in the query string are properly urlencoded.
     *
     * @param string $query
     * @return string
     */
    private function filterQuery($query)
    {
        if (!empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }
        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->filterQueryOrFragment($key);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->filterQueryOrFragment($key),
                $this->filterQueryOrFragment($value)
            );
        }
        return implode('&', $parts);
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    private function splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (1 === count($data)) {
            $data[] = null;
        }
        return $data;
    }

    /**
     * Filter a query string key or value, or a fragment.
     *
     * @param string $value
     * @return string
     */
    private function filterQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMITERS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * @param null|string $fragment
     * @return string
     */
    private function filterFragment($fragment)
    {
        if (null === $fragment) {
            $fragment = '';
        }
        if (!empty($fragment) && strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }
        return $this->filterQueryOrFragment($fragment);
    }
}