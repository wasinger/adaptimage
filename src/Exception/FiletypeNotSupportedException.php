<?php
namespace Wa72\AdaptImage\Exception;


class FiletypeNotSupportedException extends \RuntimeException
{
    /**
     * FiletypeNotSupportedException constructor
     *
     * @param string $pathname
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($pathname, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('File "%s" is no supported bitmap image type (not readable by getimagesize()).', $pathname);
        parent::__construct($message, $code, $previous);
    }
}