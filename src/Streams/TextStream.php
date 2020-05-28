<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use Lum\HttpClient\Exceptions\InvalidArgumentException;

/**
 * Class TextStream
 *
 * @package Lum\HttpClient\Streams
 */
class TextStream extends SocketStream
{
    /**
     * TextStream constructor.
     *
     * @param string $stream value
     * @param array $options params
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $stream, array $options = [])
    {
        $options = (object)$options;
        if (!isset($options->mime)) {
            $options->mime = 'text/plain';
        }
        $dataUrl = 'data:'.$options->mime;
        if (isset($options->encoding)) {
            $dataUrl .= ';'.$options->encoding;
        }
        $dataUrl .= ','.$stream;
        if (false === ($stream = fopen($dataUrl, 'rb'))) {
            throw new InvalidArgumentException(
                error_get_last()['message']
            );
        }
        parent::__construct($stream);
        $this->withHeader('Content-Type', $options->mime);
    }
}
