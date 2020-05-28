<?php
namespace Lum\HttpClient\Exceptions;

use Throwable;

/**
 * Class RuntimeException
 *
 * @package Lum\HttpClient\Exceptions
 */
class RuntimeException extends HttpClientException
{
    /**
     * RuntimeException constructor.
     *
     * @param string $message
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", $data = null, Throwable $previous = null)
    {
        parent::__construct(HttpClientError::RUNTIME_ERROR, $message, $data, $previous);
    }
}