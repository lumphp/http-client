<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\InvalidArgumentException;
use Lum\HttpClient\Streams\Stream;
use Psr\Http\Message\{ResponseInterface, StreamInterface};

/**
 * Class Response
 *
 * @package Lum\HttpClient
 */
final class Response implements ResponseInterface
{
    use MessageTrait;

    private const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
    /**
     * @var mixed|string $reasonPhrase
     */
    private $reasonPhrase;
    /**
     * @var int $statusCode
     */
    private $statusCode;

    /**
     * Response constructor.
     *
     * @param int $status Status code
     * @param array $headers Response headers
     * @param string|resource|StreamInterface|null $body Response body
     * @param string $version Protocol version
     * @param string|null $reason Reason phrase (when empty a default will be used based on the status code)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        $this->reasonPhrase = '';
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
        $this->statusCode = $status;
        $this->setHeaders($headers);
        if (null === $reason && isset(self::PHRASES[$this->statusCode])) {
            $this->reasonPhrase = self::PHRASES[$status];
        } else {
            $this->reasonPhrase = $reason ?? '';
        }
        $this->protocol = $version;
    }

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase() : string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    public function withStatus($code, $reasonPhrase = '') : Response
    {
        if (!is_int($code) && !is_string($code)) {
            throw new InvalidArgumentException('Status code has to be an integer');
        }
        $code = (int)$code;
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException('Status code has to be an integer between 100 and 599');
        }
        $new = clone $this;
        $new->statusCode = $code;
        if ((null === $reasonPhrase || '' === $reasonPhrase) && isset(self::PHRASES[$new->statusCode])) {
            $reasonPhrase = self::PHRASES[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }
}
