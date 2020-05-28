<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use finfo;
use Lum\HttpClient\Exceptions\InvalidArgumentException;

/**
 * Class FileStream
 *
 * @package Lum\HttpClient\Streams
 */
class FileStream extends SocketStream
{
    /**
     * @var string
     */
    private $filename = '';

    /**
     * FileStream constructor.
     *
     * @param string $path to file
     * @param string|null $filename client file name
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $path, string $filename = '')
    {
        if (false === ($stream = fopen($path, 'rb'))) {
            throw new InvalidArgumentException(
                error_get_last()['message']
            );
        }
        if (empty($filename)) {
            $filename = basename($path);
        }
        $this->filename = $filename;
        parent::__construct($stream);
        $mime = (new finfo)->file($path, FILEINFO_MIME_TYPE);
        if (false === $mime) {
            $mime = 'application/binary';
        }
        $this->withHeader('Content-Type', $mime);
    }

    /**
     * Get client file name
     *
     * @return string
     */
    public function getClientFilename() : string
    {
        return $this->filename;
    }
}
