<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use Lum\HttpClient\Exceptions\RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Class WrapStream
 *
 * @package Lum\HttpClient\Streams
 */
class WrapStream implements StreamInterface
{
    use MultipartHeaderTrait;

    /**
     * @var StreamInterface
     */
    private $decoratedStream;
    /**
     * @var int
     */
    private $offset;

    /**
     * Class constructor
     *
     * @param StreamInterface $decoratedStream
     * @param int $offset
     */
    public function __construct(StreamInterface $decoratedStream, ?int $offset)
    {
        $this->decoratedStream = $decoratedStream;
        $this->offset = (int)$offset;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function __toString()
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->decoratedStream->close();
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return $this->decoratedStream->detach();
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->decoratedStream->getSize() - $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        return $this->decoratedStream->tell() - $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return $this->decoratedStream->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->decoratedStream->isSeekable();
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence == SEEK_SET) {
            $this->decoratedStream->seek($offset + $this->offset, $whence);

            return;
        }
        $this->decoratedStream->seek($offset, $whence);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->decoratedStream->isWritable();
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function write($string)
    {
        if ($this->tell() < 0) {
            throw new RuntimeException('Cannot write to stream');
        }

        return $this->decoratedStream->write($string);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->decoratedStream->isReadable();
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function read($length)
    {
        if ($this->tell() < 0) {
            throw new RuntimeException('Cannot read from stream');
        }

        return $this->decoratedStream->read($length);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function getContents()
    {
        if ($this->tell() < 0) {
            throw new RuntimeException('Cannot get contents from stream');
        }

        return $this->decoratedStream->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return $this->decoratedStream->getMetadata($key);
    }
}
