<?php

declare(strict_types=1);

namespace Henrik\HttpClient\Trait;

use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait ResponseTrait
{
    use MessageTrait;

    private const RESPONSE_PHRASES = [
        StatusCodeInterface::STATUS_CONTINUE                        => 'Continue',
        StatusCodeInterface::STATUS_SWITCHING_PROTOCOLS             => 'Switching Protocols',
        StatusCodeInterface::STATUS_PROCESSING                      => 'Processing',
        StatusCodeInterface::STATUS_EARLY_HINTS                     => 'Early Hints',
        StatusCodeInterface::STATUS_OK                              => 'OK',
        StatusCodeInterface::STATUS_CREATED                         => 'Created',
        StatusCodeInterface::STATUS_ACCEPTED                        => 'Accepted',
        StatusCodeInterface::STATUS_NON_AUTHORITATIVE_INFORMATION   => 'Non-Authoritative Information',
        StatusCodeInterface::STATUS_NO_CONTENT                      => 'No Content',
        StatusCodeInterface::STATUS_RESET_CONTENT                   => 'Reset Content',
        StatusCodeInterface::STATUS_PARTIAL_CONTENT                 => 'Partial Content',
        StatusCodeInterface::STATUS_MULTI_STATUS                    => 'Multi-Status',
        StatusCodeInterface::STATUS_ALREADY_REPORTED                => 'Already Reported',
        StatusCodeInterface::STATUS_IM_USED                         => 'IM Used',
        StatusCodeInterface::STATUS_MULTIPLE_CHOICES                => 'Multiple Choices',
        StatusCodeInterface::STATUS_MOVED_PERMANENTLY               => 'Moved Permanently',
        StatusCodeInterface::STATUS_FOUND                           => 'Found',
        StatusCodeInterface::STATUS_SEE_OTHER                       => 'See Other',
        StatusCodeInterface::STATUS_NOT_MODIFIED                    => 'Not Modified',
        StatusCodeInterface::STATUS_USE_PROXY                       => 'Use Proxy',
        StatusCodeInterface::STATUS_RESERVED                        => 'Status Reserved',
        StatusCodeInterface::STATUS_TEMPORARY_REDIRECT              => 'Temporary Redirect',
        StatusCodeInterface::STATUS_PERMANENT_REDIRECT              => 'Permanent Redirect',
        StatusCodeInterface::STATUS_BAD_REQUEST                     => 'Bad Request',
        StatusCodeInterface::STATUS_UNAUTHORIZED                    => 'Unauthorized',
        StatusCodeInterface::STATUS_PAYMENT_REQUIRED                => 'Payment Required',
        StatusCodeInterface::STATUS_FORBIDDEN                       => 'Forbidden',
        StatusCodeInterface::STATUS_NOT_FOUND                       => 'Not Found',
        StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
        StatusCodeInterface::STATUS_NOT_ACCEPTABLE                  => 'Not Acceptable',
        StatusCodeInterface::STATUS_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
        StatusCodeInterface::STATUS_REQUEST_TIMEOUT                 => 'Request Timeout',
        StatusCodeInterface::STATUS_CONFLICT                        => 'Conflict',
        StatusCodeInterface::STATUS_GONE                            => 'Gone',
        StatusCodeInterface::STATUS_LENGTH_REQUIRED                 => 'Length Required',
        StatusCodeInterface::STATUS_PRECONDITION_FAILED             => 'Precondition Failed',
        StatusCodeInterface::STATUS_PAYLOAD_TOO_LARGE               => 'Payload Too Large',
        StatusCodeInterface::STATUS_URI_TOO_LONG                    => 'URI Too Long',
        StatusCodeInterface::STATUS_UNSUPPORTED_MEDIA_TYPE          => 'Unsupported Media Type',
        StatusCodeInterface::STATUS_RANGE_NOT_SATISFIABLE           => 'Range Not Satisfiable',
        StatusCodeInterface::STATUS_EXPECTATION_FAILED              => 'Expectation Failed',
        StatusCodeInterface::STATUS_IM_A_TEAPOT                     => 'I\'m a teapot',
        StatusCodeInterface::STATUS_MISDIRECTED_REQUEST             => 'Misdirected Request',
        StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY            => 'Unprocessable Entity',
        StatusCodeInterface::STATUS_LOCKED                          => 'Locked',
        StatusCodeInterface::STATUS_FAILED_DEPENDENCY               => 'Failed Dependency',
        StatusCodeInterface::STATUS_TOO_EARLY                       => 'Too Early',
        StatusCodeInterface::STATUS_UPGRADE_REQUIRED                => 'Upgrade Required',
        StatusCodeInterface::STATUS_PRECONDITION_REQUIRED           => 'Precondition Required',
        StatusCodeInterface::STATUS_TOO_MANY_REQUESTS               => 'Too Many Requests',
        StatusCodeInterface::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS   => 'Unavailable For Legal Reasons',
        StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
        StatusCodeInterface::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
        StatusCodeInterface::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
        StatusCodeInterface::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
        StatusCodeInterface::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
        StatusCodeInterface::STATUS_VERSION_NOT_SUPPORTED           => 'HTTP Version Not Supported',
        StatusCodeInterface::STATUS_VARIANT_ALSO_NEGOTIATES         => 'Variant Also Negotiates',
        StatusCodeInterface::STATUS_INSUFFICIENT_STORAGE            => 'Insufficient Storage',
        StatusCodeInterface::STATUS_LOOP_DETECTED                   => 'Loop Detected',
        StatusCodeInterface::STATUS_NOT_EXTENDED                    => 'Not Extended',
        StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
    ];

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int    $code         the 3-digit integer result code to set
     * @param string $reasonPhrase the reason phrase to use with the
     *                             provided status code; if none is provided, implementations MAY
     *                             use the defaults as suggested in the HTTP specification
     *
     * @return ResponseInterface
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->setStatus($code, $reasonPhrase);

        return $new;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @return string reason phrase; must return an empty string if none present
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param int                                  $statusCode
     * @param string                               $reasonPhrase
     * @param StreamInterface|string|resource|null $body
     * @param string[]                             $headers
     * @param string                               $protocol
     */
    private function init(
        int $statusCode = 200,
        string $reasonPhrase = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ): void {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);
    }

    /**
     * @param int    $statusCode
     * @param string $reasonPhrase
     *
     * @throws InvalidArgumentException for invalid status code arguments
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    private function setStatus(int $statusCode, string $reasonPhrase = ''): void
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(
                'Response status code "%d" is not valid. It must be in 100..599 range.',
                $statusCode
            ));
        }

        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase ?: (self::RESPONSE_PHRASES[$statusCode] ?? '');
    }
}