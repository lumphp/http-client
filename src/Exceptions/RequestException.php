<?php
declare(strict_types=1);
namespace Lum\HttpClient\Exceptions;

use Throwable;

/**
 * Class RequestException
 *
 * @package Lum\HttpClient\Exceptions
 */
class RequestException extends HttpClientException
{
    /**
     * RequestException constructor.
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
        parent::__construct(HttpClientError::REQUEST_ERROR, $message, $data, $previous);
    }
}
