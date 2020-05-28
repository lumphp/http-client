<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Trait RequestTrait
 *
 * @package Lum\HttpClient
 */
trait RequestTrait
{
    /** @var string */
    private $method;
    /** @var string|null */
    private $requestTarget;
    /** @var UriInterface|null */
    private $uri;

    /**
     * @return string
     */
    public function getRequestTarget() : string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }
        if ('' === $target = $this->uri->getPath()) {
            $target = '/';
        }
        if ('' !== $this->uri->getQuery()) {
            $target .= '?'.$this->uri->getQuery();
        }

        return $target;
    }

    /**
     * @param $requestTarget
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withRequestTarget($requestTarget) : self
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @param $method
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withMethod($method) : self
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Method must be a string');
        }
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * @return UriInterface
     */
    public function getUri() : UriInterface
    {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @return $this
     */
    public function withUri(UriInterface $uri, $preserveHost = false) : self
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    /**
     * @See http://tools.ietf.org/html/rfc7230#section-5.4
     */
    private function updateHostFromUri() : void
    {
        if ('' === $host = $this->uri->getHost()) {
            return;
        }
        if (null !== ($port = $this->uri->getPort())) {
            $host .= ':'.$port;
        }
        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $this->headerNames['host'] = $header = 'Host';
        }
        $this->headers = [$header => [$host]] + $this->headers;
    }
}
