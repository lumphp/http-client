<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use Lum\HttpClient\Exceptions\RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Class SocketStream
 *
 * @package Lum\HttpClient\Streams
 */
class SocketStream implements StreamInterface
{
    use MultipartHeaderTrait;

    /**
     * @var resource
     */
    private $stream;
    /**
     * @var object
     */
    private $options;

    /**
     * SocketStream constructor.
     *
     * @param resource $stream handle
     * @param array $options params
     */
    public function __construct($stream, array $options = [])
    {
        $this->options = (object)$options;
        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return stream_get_contents($this->stream);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function tell()
    {
        if (false === ($position = ftell($this->stream))) {
            throw new RuntimeException('Cannot get stream offset');
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (-1 === fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException(
                'Stream does not support seeking'
            );
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (0 === func_num_args()) {
            return stream_get_meta_data($this->stream);
        } else {
            return stream_get_meta_data($this->stream)[$key];
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function read($length)
    {
        if (false === ($data = fread($this->stream, $length))) {
            throw new RuntimeException('Cannot read from stream');
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $size = (int)fstat($this->stream)['size'];
        if (!$size) {
            if (isset($this->options->contentLength)) {
                $size = $this->options->contentLength;
            }
        }

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $stream = $this->detach();
        fclose($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');

        return (strstr($mode, 'r') or strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');

        return (strstr($mode, 'x') or strstr($mode, 'w') or strstr($mode, 'c') or strstr($mode, 'a') or
            strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function write($string)
    {
        if (false === ($length = fwrite($this->stream, $string))) {
            throw new RuntimeException('Cannot write to stream');
        }

        return $length;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->getContents();
    }
}
