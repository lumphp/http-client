<?php
declare(strict_types=1);
namespace Lum\HttpClient\Exceptions;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

/**
 * Class HttpClientException
 */
class HttpClientException extends Exception implements ClientExceptionInterface
{
    private $data;

    /**
     * HttpClientException constructor.
     *
     * @param int $code
     * @param string $message
     * @param null $data
     * @param Throwable|null $previous
     */
    public function __construct(
        int $code = 0,
        string $message = "",
        $data = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }
}