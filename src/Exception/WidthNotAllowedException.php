<?php
namespace Wa72\AdaptImage\Exception;


class WidthNotAllowedException extends \OutOfBoundsException
{
    /**
     * WidthNotAllowedException constructor
     *
     * @param string $width
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($width, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('%s is not a defined image width.', $width);
        parent::__construct($message, $code, $previous);
    }
}