<?php
namespace Wa72\AdaptImage\Exception;


class ImageClassNotRegisteredException extends \OutOfBoundsException
{
    /**
     * ImageClassNotRegisteredException constructor
     *
     * @param string $imageclass
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($imageclass, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('ResponsiveImageClass "%s" is not registered in the ResponsiveImageHelper.', $imageclass);
        parent::__construct($message, $code, $previous);
    }
}