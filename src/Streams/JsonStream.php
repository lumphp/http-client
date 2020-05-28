<?php
declare(strict_types=1);
namespace Lum\HttpClient\Streams;

use Lum\HttpClient\Exceptions\InvalidArgumentException;

/**
 * Class JsonStream
 *
 * @package Lum\HttpClient\Streams
 */
class JsonStream extends TextStream
{
    /**
     * JsonStream constructor.
     *
     * @param mixed $data to encode
     * @param int $encodingOptions encoding options
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($data, $encodingOptions = 79)
    {
        $json = $this->encodeJson($data, $encodingOptions);
        $mime = 'application/json';
        parent::__construct($json);
        $this->withHeader('Content-Type', $mime);
    }

    /**
     * Encode JSON
     *
     * @param mixed $data to encode
     * @param int $encodingOptions encoding options
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function encodeJson($data, int $encodingOptions) : string
    {
        // reset error
        json_encode(null);
        $json = json_encode($data, $encodingOptions);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to encode data to JSON in %s: %s',
                    __CLASS__,
                    json_last_error_msg()
                )
            );
        }

        return $json;
    }
}
