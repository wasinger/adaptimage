<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Image\ImageInterface;
use Imagine\Filter\FilterInterface;

class Sharpen implements FilterInterface
{
    public function __construct()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $image->effects()->sharpen();
        return $image;
    }
}