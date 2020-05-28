<?php
namespace Lum\HttpClient\Exceptions;

/**
 * Class HttpClientError
 *
 * @package Lum\HttpClient\Exceptions
 */
final class HttpClientError
{
    //common error
    const INVALID_ARGUMENT = 10001;
    const RUNTIME_ERROR = 10002;
    //http client error
    const NETWORK_ERROR = 100001;
    const REQUEST_ERROR = 100002;
}