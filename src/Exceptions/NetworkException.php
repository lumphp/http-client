<?php
declare(strict_types=1);
namespace Lum\HttpClient\Exceptions;

use Throwable;

/**
 * Class NetworkException
 *
 * @package Lum\HttpClient\Exceptions
 */
class NetworkException extends HttpClientException
{
    /**
     * NetworkException constructor.
     *
     * @param string $message
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        $data = null,
        Throwable $previous = null
    ) {
        parent::__construct(HttpClientError::NETWORK_ERROR, $message, $data, $previous);
    }
}
