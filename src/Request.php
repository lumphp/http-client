<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Streams\Stream;
use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};

/**
 * Class Request
 *
 * @package Lum\HttpClient
 */
final class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * Request constructor.
     *
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     *
     * @throws Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }
        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;
        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
    }
}
