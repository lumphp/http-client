<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\InvalidArgumentException;
use Lum\HttpClient\Exceptions\RequestException;
use Lum\HttpClient\Streams\JsonStream;
use Lum\HttpClient\Streams\MultipartStream;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class DefaultClient
 *
 * @package Lum\HttpClient
 */
class DefaultClient implements ClientInterface
{
    const BUFFER_SIZE = 4096;
    const CRLF = "\r\n";
    /**
     * @var object
     */
    private $options;
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var Transport
     */
    private $transport;
    /**
     * @var string
     */
    private $userAgent = "Psr18 Compatible HTTP Client";

    /**
     * HttpClient constructor.
     *
     * @param ResponseInterface $response prototype
     * @param object $options |null flags
     */
    public function __construct(ResponseInterface $response, ?object $options = null)
    {
        $this->options = $options;
        $this->response = $response;
        $this->transport = new Transport($options);
        if (!isset($this->options->followLocation)) {
            $this->options->followLocation = true;
        }
        if (!isset($this->options->maxRedirects)) {
            $this->options->maxRedirects = 5;
        }
        if (!isset($this->options->waitResponse)) {
            $this->options->waitResponse = true;
        }
        if (!isset($this->options->requestFullUri)) {
            $this->options->requestFullUri = false;
        }
        if (!isset($this->options->ssl)) {
            $this->options->ssl = [];
        }
    }

    /**
     * Build message header
     *
     * @param RequestInterface $request instance
     *
     * @return string
     * @throws RequestException
     */
    private function buildMessage(RequestInterface $request) : string
    {
        $method = $request->getMethod();
        if (!$method) {
            throw new RequestException(
                'Request method is not defined', $request
            );
        }
        $protocol = $request->getProtocolVersion();
        if (!$protocol) {
            $protocol = '1.1';
        }
        $target = $this->options->requestFullUri ? (string)$request->getUri() : $request->getRequestTarget();
        if (!$target) {
            $target = '/';
        }
        $message = $method.' '.$target.' HTTP/'.$protocol.self::CRLF;
        $body = $request->getBody();
        if ($body->getSize() and !in_array($method, ['POST', 'PUT', 'PATCH'])) {
            throw new RequestException(
                sprintf(
                    'Method %s does not support body sending',
                    $method
                ), $request
            );
        }
        if (!$request->hasHeader('User-Agent')) {
            $request = $request->withHeader('User-Agent', $this->userAgent);
        }
        if ($body instanceof JsonStream) {
            $request = $request->withHeader('Content-Type', 'application/json; charset=UTF-8');
        } elseif ($body instanceof MultipartStream) {
            $request = $request->withHeader(
                'Content-Type',
                'multipart/form-data; boundary='.$body->getBoundary()
            );
        }
        $request = $request->withHeader('Content-Length', (string)((int)$body->getSize()))->withHeader(
            'Connection',
            'close'
        );
        $message .= Misc::serializePsr7Headers($request->getHeaders());

        return $message.self::CRLF;
    }

    /**
     * Parse message header
     *
     * @param string $message header
     *
     * @return array
     */
    private function parseMessage(string $message) : array
    {
        $headers = [];
        $version = $code = $reasonPhrase = null;
        foreach (explode(self::CRLF, $message) as $line) {
            if (!$line) {
                continue;
            }
            if (0 === strpos($line, 'HTTP/')) {
                $line = substr($line, 5);
                [$version, $code, $reasonPhrase] = explode(' ', $line);
            } else {
                [$name, $value] = explode(':', $line, 2);
                $name = trim($name);
                $name = strtolower($name);
                $value = trim($value);
                $headers[$name] = $value;
            }
        }

        return [$version, $code, $reasonPhrase, $headers];
    }

    /**
     * Redirect request 3xx
     *
     * @param RequestInterface $request instance
     * @param string $target URL
     *
     * @return ResponseInterface
     * @throws Exceptions\NetworkException
     * @throws Exceptions\RuntimeException
     * @throws InvalidArgumentException
     * @throws RequestException
     */
    private function redirect(RequestInterface $request, string $target) : ResponseInterface
    {
        if (!$this->options->maxRedirects) {
            throw new RequestException('Too many redirects', $request);
        }
        $this->options->maxRedirects--;
        if (Misc::isRelativeUrl($target)) {
            [$path, $query] = Misc::extractRelativeUrlComponents($target);
            $uri = $request->getUri()->withPath($path)->withQuery($query);
        } else {
            $uriPrototype = get_class($request->getUri());
            $uri = new $uriPrototype($target);
            $request = $request->withHeader('Host', $uri->getHost());
            $target = (($uri->getPath() ? $uri->getPath() : '/').
                ($uri->getQuery() ? ('?'.$uri->getQuery()) : ''));
        }
        $request = $request->withUri($uri)->withRequestTarget($target);

        return $this->sendRequest($request);
    }

    /**
     * Send HTTP request
     *
     * @param RequestInterface $request instance
     *
     * @return ResponseInterface
     * @throws Exceptions\NetworkException
     * @throws Exceptions\RuntimeException
     * @throws InvalidArgumentException
     * @throws RequestException
     */
    public function sendRequest(RequestInterface $request) : ResponseInterface
    {
        $message = $this->buildMessage($request);
        $this->transport->setRequest($request);
        $this->transport->connect();
        $this->transport->send($message);
        $body = $request->getBody();
        while (!$body->eof()) {
            $buffer = $body->read(self::BUFFER_SIZE);
            $this->transport->send($buffer);
        }
        if (!$this->options->waitResponse) {
            return $this->response;
        }
        $message = $this->transport->readMessage();
        if (!$message) {
            throw new RequestException('Empty response header', $request);
        }
        [$version, $code, $reasonPhrase, $headers] = $this->parseMessage($message);
        if ($code >= 300 and $code <= 308) {
            if ($headers['location'] and $this->options->followLocation) {
                return $this->redirect($request, $headers['location']);
            }
        }
        $this->response = $this->response->withProtocolVersion($version)->withStatus($code, $reasonPhrase);
        foreach ($headers as $name => $value) {
            $this->response = $this->response->withHeader($name, $value);
        }
        $body = $this->transport->createBodyStream(
            [
                'contentLength' => (int)$this->response->getHeaderLine('Content-Length'),
            ]
        );
        $this->response = $this->response->withBody($body);

        return $this->response;
    }
}
