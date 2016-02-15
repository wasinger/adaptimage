<?php
namespace Wa72\AdaptImage\Exception;


class ImageFileNotFoundException extends \RuntimeException
{
    /**
     * ImageFileNotFoundException constructor
     *
     * @param string $pathname
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($pathname, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Image file "%s" not found or not readable.', $pathname);
        parent::__construct($message, $code, $previous);
    }
}