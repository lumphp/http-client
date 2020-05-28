<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use ArrayIterator;
use Lum\HttpClient\DefaultClient;
use Lum\HttpClient\Exceptions\InvalidArgumentException;
use Lum\HttpClient\Exceptions\RuntimeException;
use Lum\HttpClient\Misc;
use Psr\Http\Message\StreamInterface;

/**
 * Class MultipartStream
 *
 * @package Lum\HttpClient\Streams
 */
class MultipartStream implements StreamInterface
{
    /**
     * @var string
     */
    private $boundary;
    /**
     * @var ArrayIterator
     */
    private $arrayIterator;
    /**
     * @var StreamInterface
     */
    private $streamCursor;
    /**
     * @var int
     */
    private $streamIndex;
    /**
     * @var array
     */
    private $patterns = [
        'plain' => ('Content-Disposition: form-data; name="%s"%s'.DefaultClient::CRLF.DefaultClient::CRLF.
            '%s'),
        'file' => ('Content-Disposition: form-data; name="%s"; filename="%s"%s'.DefaultClient::CRLF.
            DefaultClient::CRLF),
    ];

    /**
     * MultipartStream constructor.
     *
     * @param array $data
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(array $data)
    {
        $this->arrayIterator = new ArrayIterator;
        $data = $this->arrayToPlain($data);
        foreach ($data as $key => $value) {
            $isFileStream = $value instanceof FileStream;
            $pattern = $this->patterns[$isFileStream ? 'file' : 'plain'];
            $metaHeader = ($value instanceof SocketStream) ? Misc::serializePsr7Headers(
                $value->getHeaders()
            ) : '';
            if ($metaHeader) {
                $metaHeader = DefaultClient::CRLF.$metaHeader;
            }
            $meta = '--'.$this->getBoundary().DefaultClient::CRLF;
            $meta .= ($isFileStream ? sprintf(
                $pattern,
                $key,
                $value->getClientFilename(),
                $metaHeader
            ) : sprintf($pattern, $key, $metaHeader, !($value instanceof StreamInterface) ? $value : ''));
            $metaStream = new TextStream($meta);
            $this->arrayIterator->append($metaStream);
            if ($value instanceof StreamInterface) {
                $this->arrayIterator->append($value);
            }
            $this->arrayIterator->append(new TextStream(DefaultClient::CRLF));
        }
        if ($data) {
            $this->arrayIterator->append(
                new TextStream(
                    '--'.$this->getBoundary().'--'
                )
            );
            $this->streamIndex = 0;
            $this->streamCursor = $this->arrayIterator->offsetGet(0);
        }
    }

    /**
     * Convert array to plain key -> value pairs
     *
     * @param array $data array
     * @param string $prefix parent prefix
     *
     * @return MultipartStream[]
     * @throws RuntimeException
     */
    private function arrayToPlain(array $data, $prefix = '')
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value instanceof MultipartStream) {
                throw new RuntimeException(
                    MultipartStream::class.' disabled in nested multipart data'
                );
            }
            if ($prefix) {
                $index = $prefix.'['.$key.']';
            } else {
                $index = $key;
            }
            if (is_array($value)) {
                $result = $result + $this->arrayToPlain($value, $index);
            } else {
                $result[$index] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $data = '';
        foreach ($this->arrayIterator as $stream) {
            $data .= $stream->getContents();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (!$this->streamCursor) {
            return '';
        }
        $data = $this->streamCursor->read($length);
        $bytes = strlen($data);
        if ($bytes < $length) {
            if (!$this->eof()) {
                $this->streamCursor = $this->arrayIterator->offsetGet(++$this->streamIndex);
                $data .= $this->read($length - $bytes);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (!$this->streamCursor) {
            return true;
        }

        return ($this->streamCursor and
            $this->streamCursor->eof() and $this->arrayIterator->count() === $this->streamIndex + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        foreach ($this->arrayIterator as $stream) {
            $stream->detach();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        foreach ($this->arrayIterator as $stream) {
            $stream->close();
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function tell()
    {
        throw new RuntimeException('Cannot get current position');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function write($string)
    {
        throw new RuntimeException('Stream does not support writing');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        foreach ($this->arrayIterator as $stream) {
            $stream->rewind();
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new RuntimeException(
            'Stream does not support seeking'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $size = 0;
        foreach ($this->arrayIterator as $stream) {
            $size += $stream->getSize();
        }

        return $size;
    }

    /**
     * Get boundary unique identifier
     */
    public function getBoundary()
    {
        if (null === $this->boundary) {
            $abc = implode(
                array_merge(
                    range('A', 'Z'),
                    range('a', 'z'),
                    range(0, 9)
                )
            );
            $this->boundary = substr(str_shuffle($abc), -12);
        }

        return $this->boundary;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getContents();
    }
}
