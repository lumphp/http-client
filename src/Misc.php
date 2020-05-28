<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\InvalidArgumentException;

/**
 * Class Misc
 *
 * @package Lum\HttpClient
 */
class Misc
{
    /**
     * Stringify array of headers
     *
     * @param array $headers list
     *
     * @return string
     */
    public static function serializePsr7Headers(array $headers) : string
    {
        $message = '';
        foreach ($headers as $name => $values) {
            $message .= $name.': '.implode(', ', $values).DefaultClient::CRLF;
        }

        return $message;
    }

    /**
     * Check if URL relative
     *
     * @param string $url target
     *
     * @return bool
     */
    public static function isRelativeUrl(string $url) : bool
    {
        $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

        return !preg_match($pattern, $url);
    }

    /**
     * Parse URL and get components
     *
     * @param string $url target
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function extractRelativeUrlComponents(string $url) : array
    {
        if (false === ($url = parse_url($url))) {
            throw new InvalidArgumentException('Malformed URL: '.$url);
        }

        return [$url['path'] ?? '/', $url['query'] ?? ''];
    }

    /**
     * Convert SSL options keys
     *
     * @param array $options params
     *
     * @return array
     */
    public static function convertSslOptionsKeys(array $options) : array
    {
        $keys = array_keys($options);
        $values = array_values($options);
        $keys = array_map(
            function ($key) {
                return preg_replace_callback(
                    '~[A-Z][a-z]~',
                    function ($matches) {
                        return '_'.strtolower($matches[0]);
                    },
                    $key
                );
            },
            $keys
        );

        return array_combine($keys, $values);
    }
}
