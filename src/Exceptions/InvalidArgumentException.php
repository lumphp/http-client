<?php
namespace Lum\HttpClient\Exceptions;

use Throwable;

/**
 * Class InvalidArgumentException
 *
 * @package Lum\HttpClient\Exceptions
 */
class InvalidArgumentException extends HttpClientException
{
    /**
     * InvalidArgumentException constructor.
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
        parent::__construct(HttpClientError::INVALID_ARGUMENT, $message, $data, $previous);
    }
}