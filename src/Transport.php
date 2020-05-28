<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\NetworkException;
use Lum\HttpClient\Exceptions\RuntimeException;
use Lum\HttpClient\Streams\SocketStream;
use Psr\Http\Message\RequestInterface;

/**
 * Class Transport
 *
 * @package Lum\HttpClient
 */
class Transport
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var object
     */
    private $options;
    /**
     * @var resource
     */
    private $connection;

    /**
     * Transport constructor.
     *
     * @param object $options flags
     */
    public function __construct(object $options)
    {
        if (!isset($options->timeout)) {
            $options->timeout = 30;
        }
        $this->options = $options;
    }

    /**
     * Set request instance
     *
     * @param RequestInterface $request instance
     *
     * @return void
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get preferred SSL transport version
     *
     * @return string
     * @throws RuntimeException
     */
    private function getPreferredSslProtocol() : string
    {
        $transports = stream_get_transports();
        $sslTransports = array_filter(
            $transports,
            function ($transport) {
                return (0 === strpos($transport, 'ssl')) or (0 === strpos($transport, 'tls'));
            }
        );
        if (!$sslTransports) {
            $transports = implode(', ', $transports);
            throw new RuntimeException(
                'No SSL/TLS transports found, avail transports is: ['.$transports.']'
            );
        }
        rsort($sslTransports);

        return reset($sslTransports);
    }

    /**
     * Built target URI
     *
     * @return string
     * @throws RuntimeException
     */
    private function buildUri() : string
    {
        if (isset($this->options->proxy)) {
            return $this->options->proxy;
        }
        $isSecure = $this->request->getUri()->getScheme() === 'https';
        $transport = $isSecure ? $this->options->sslProtocol ?? $this->getPreferredSslProtocol() : 'tcp';
        $port = $this->request->getUri()->getPort();
        if (!$port) {
            $port = $isSecure ? 443 : 80;
        }
        $host = $this->request->getUri()->getHost();

        return sprintf('%s://%s:%s', $transport, $host, $port);
    }

    /**
     * Create socket stream connection
     *
     * @return void
     * @throws NetworkException
     * @throws RuntimeException
     */
    public function connect()
    {
        $errno = $errorString = null;
        $uri = $this->buildUri();
        $timeout = $this->options->timeout;
        $flags = STREAM_CLIENT_CONNECT;
        $arguments = [$uri, $errno, $errorString, $timeout, $flags];
        $context = [];
        if ($this->options->ssl) {
            $context['ssl'] = Misc::convertSslOptionsKeys($this->options->ssl);
        }
        if ($context) {
            $arguments[] = stream_context_create($context);
        }
        if (false === ($this->connection = stream_socket_client(...$arguments))) {
            throw new NetworkException(
                $errorString ? $errorString : 'Unknown network error', $this->request
            );
        }
        stream_set_blocking($this->connection, true);
    }

    /**
     * Send data to socket stream
     *
     * @param mixed $data to send
     *
     * @return void
     * @throws NetworkException
     */
    public function send($data)
    {
        if ('' === $data) {
            return;
        }
        $string = (string)$data;
        if (false === fwrite($this->connection, $string, strlen($string))) {
            throw new NetworkException(
                error_get_last()['message'], $this->request
            );
        }
    }

    /**
     * Read header message from socket stream
     *
     * @return string
     * @throws NetworkException
     */
    public function readMessage() : string
    {
        $message = '';
        while (!stream_get_meta_data($this->connection)['eof']) {
            if (!$this->connection) {
                break;
            }
            $symbol = fgetc($this->connection);
            if (false === $symbol) {
                throw new NetworkException(
                    'Cannot read data from socket stream', $this->request
                );
            }
            $message .= $symbol;
            if (DefaultClient::CRLF.DefaultClient::CRLF === substr($message, -4)) {
                break;
            }
        }

        return rtrim($message, DefaultClient::CRLF);
    }

    /**
     * Create body stream
     *
     * @param array $options of stream
     *
     * @return SocketStream
     */
    public function createBodyStream(array $options = []) : SocketStream
    {
        return new SocketStream($this->connection, $options);
    }

    /**
     * Close socket stream connection
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->connection) {
            fclose($this->connection);
        }
    }
}
