<?php
namespace Wa72\AdaptImage\ImagineFilter;


use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;

class Interlace implements FilterInterface
{
    /** @var string One of the ImageInterface::INTERLACE_XXX constants */
    private $interlace;

    /**
     * @param string $interlace One of the ImageInterface::INTERLACE_XXX constants
     */
    public function __construct($interlace)
    {
        $this->interlace = $interlace;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $image->interlace($this->interlace);
        return $image;
    }
}