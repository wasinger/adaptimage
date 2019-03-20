<?php
namespace Wa72\AdaptImage\Exception;


class ImageTypeNotSupportedException extends \RuntimeException
{
    /**
     *
     *
     * @param string $mime_type
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($mime_type, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('File type "%s" not supported.', $mime_type);
        parent::__construct($message, $code, $previous);
    }
}