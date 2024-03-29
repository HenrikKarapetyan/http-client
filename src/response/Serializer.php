<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/9/8  11:11:38.
 */

namespace henrik\http_client\response;



use henrik\http_client\Response;
use henrik\http_client\Stream;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;

final class Serializer extends AbstractSerializer
{
    /**
     * Deserialize a response string to a response instance.
     *
     * @param string $message
     * @return ResponseInterface
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromString($message)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($message);
        return static::fromStream($stream);
    }

    /**
     * Parse a response from a stream.
     *
     * @param StreamInterface $stream
     * @return ResponseInterface
     * @throws InvalidArgumentException when the stream is not readable.
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromStream(StreamInterface $stream)
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        list($version, $status, $reasonPhrase) = self::getStatusLine($stream);
        list($headers, $body)                  = self::splitStream($stream);

        return (new Response($body, $status, $headers))
            ->withProtocolVersion($version)
            ->withStatus($status, $reasonPhrase);
    }

    /**
     * Create a string representation of a response.
     *
     * @param ResponseInterface $response
     * @return string
     */
    public static function toString(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = self::serializeHeaders($response->getHeaders());
        $body         = (string) $response->getBody();
        $format       = 'HTTP/%s %d%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }
        if (! empty($body)) {
            $headers .= "\r\n\r\n";
        }

        return sprintf(
            $format,
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : ''),
            $headers,
            $body
        );
    }

    /**
     * Retrieve the status line for the message.
     *
     * @param StreamInterface $stream
     * @return array Array with three elements: 0 => version, 1 => status, 2 => reason
     * @throws UnexpectedValueException if line is malformed
     */
    private static function getStatusLine(StreamInterface $stream)
    {
        $line = self::getLine($stream);

        if (! preg_match(
            '#^HTTP/(?P<version>[1-9]\d*\.\d) (?P<status>[1-5]\d{2})(\s+(?P<reason>.+))?$#',
            $line,
            $matches
        )) {
            throw new UnexpectedValueException('No status line detected');
        }

        return [$matches['version'], $matches['status'], isset($matches['reason']) ? $matches['reason'] : ''];
    }
}
