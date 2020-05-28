<?php
declare(strict_types=1);
namespace Lum\HttpClient;

use Lum\HttpClient\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Class Uri
 *
 * @package Lum\HttpClient
 */
final class Uri implements UriInterface
{
    private const SCHEMES = [
        'http' => 80,
        'https' => 443,
    ];
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    private const CHAR_SUB_DEL_IMS = '!\$&\'\(\)\*\+,;=';
    private const CHAR_PATH = '%:@\/';
    private const CHAR_QUERY_FRAGMENT = '\?';
    /** @var string Uri scheme. */
    private $scheme = '';
    /** @var string Uri user info. */
    private $userInfo = '';
    /** @var string Uri host. */
    private $host = '';
    /** @var int|null Uri port. */
    private $port;
    /** @var string Uri path. */
    private $path = '';
    /** @var string Uri query string. */
    private $query = '';
    /** @var string Uri fragment. */
    private $fragment = '';

    /**
     * Uri constructor.
     *
     * @param string $uri
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $uri = '')
    {
        if ($uri) {
            if (false === $parts = parse_url($uri)) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }
            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->userInfo = $parts['user'] ?? '';
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment(
                $parts['fragment']
            ) : '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':'.$parts['pass'];
            }
        }
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return static::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    /**
     * @return string
     */
    public function getScheme() : string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getAuthority() : string
    {
        if ('' === $this->host) {
            return '';
        }
        $authority = $this->host;
        if ('' !== $this->userInfo) {
            $authority = $this->userInfo.'@'.$authority;
        }
        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /**
     * @return string
     */
    public function getUserInfo() : string
    {
        return $this->userInfo;
    }

    /**
     * @return string
     */
    public function getHost() : string
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort() : ?int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery() : string
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment() : string
    {
        return $this->fragment;
    }

    /**
     * @param string $scheme
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withScheme($scheme) : Uri
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string');
        }
        if ($this->scheme === $scheme = strtolower($scheme)) {
            return $this;
        }
        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /**
     * @param string $user
     * @param null $password
     *
     * @return $this
     */
    public function withUserInfo($user, $password = null) : Uri
    {
        $info = $user;
        if (null !== $password && '' !== $password) {
            $info .= ':'.$password;
        }
        if ($this->userInfo === $info) {
            return $this;
        }
        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * @param string $host
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withHost($host) : Uri
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('Host must be a string');
        }
        if ($this->host === $host = strtolower($host)) {
            return $this;
        }
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * @param int|null $port
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withPort($port) : Uri
    {
        if ($this->port === $port = $this->filterPort($port)) {
            return $this;
        }
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @param string $path
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withPath($path) : Uri
    {
        if ($this->path === $path = $this->filterPath($path)) {
            return $this;
        }
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @param string $query
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withQuery($query) : Uri
    {
        if ($this->query === $query = $this->filterQueryAndFragment($query)) {
            return $this;
        }
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @param string $fragment
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function withFragment($fragment) : Uri
    {
        if ($this->fragment === $fragment = $this->filterQueryAndFragment($fragment)) {
            return $this;
        }
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * Create a URI string from its various parts.
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     *
     * @return string
     */
    private static function createUriString(
        string $scheme,
        string $authority,
        string $path,
        string $query,
        string $fragment
    ) : string {
        $uri = '';
        if ($scheme) {
            $uri .= $scheme.':';
        }
        if ($authority) {
            $uri .= '//'.$authority;
        }
        if ($path) {
            $uri .= static::fixPath($path, $authority);
        }
        if ($query) {
            $uri .= '?'.$query;
        }
        if ('' !== $fragment) {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }

    /**
     * @param string $scheme
     * @param int $port
     *
     * @return bool
     */
    private static function isNonStandardPort(string $scheme, int $port) : bool
    {
        return !isset(static::SCHEMES[$scheme]) || $port !== static::SCHEMES[$scheme];
    }

    /**
     * @param $port
     *
     * @return int|null
     * @throws InvalidArgumentException
     */
    private function filterPort($port) : ?int
    {
        if (null === $port) {
            return null;
        }
        $port = (int)$port;
        if (0 > $port || 0xffff < $port) {
            throw new InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 0 and 65535', $port)
            );
        }

        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    /**
     * @param $path
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function filterPath($path) : string
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }

        return static::filterCharsAndEncode(
            $path,
            static::CHAR_UNRESERVED.static::CHAR_SUB_DEL_IMS.static::CHAR_PATH
        );
    }

    /**
     * @param $str
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function filterQueryAndFragment($str) : string
    {
        if (!is_string($str)) {
            throw new InvalidArgumentException('Query and fragment must be a string');
        }

        return static::filterCharsAndEncode(
            $str,
            static::CHAR_UNRESERVED.static::CHAR_SUB_DEL_IMS.static::CHAR_PATH.static::CHAR_QUERY_FRAGMENT
        );
    }

    /**
     * @param $path
     * @param string $authority
     *
     * @return string
     */
    private static function fixPath($path, string $authority) : string
    {
        if ('/' !== $path[0] && $authority) {
            return '/'.$path;
        }
        if (isset($path[1]) && '/' === $path[1] && $authority) {
            return '/'.ltrim($path, '/');
        }

        return $path;
    }

    /**
     * @param string $str
     * @param string $chars
     *
     * @return string|string[]|null
     */
    private static function filterCharsAndEncode(string $str, string $chars)
    {
        $encodedChars = '%(?![A-Fa-f0-9]{2})';
        $format = '/(?:[^%s]++|%s)/';
        $regex = sprintf($format, $chars, $encodedChars);

        return preg_replace_callback(
            $regex,
            function ($matches) {
                return $matches && isset($matches[0]) && $matches[0] ? rawurlencode($matches[0]) : '';
            },
            $str
        );
    }
}
