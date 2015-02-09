<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Image\ImageInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;

/**
 * A crop filter that crops a specified box from the center of the image
 */
class CropCenter extends Crop
{
    /**
     * Constructs a Crop filter with crop width and height values
     * The starting point will be calculated so that the given box is centered in the image
     *
     * @param BoxInterface $size
     * @param bool $upscale If true, the image will be upscaled when it is smaller than the crop size
     */
    public function __construct(BoxInterface $size, $upscale = false)
    {
        $this->size  = $size;
        $this->upscale = $upscale;
        $this->start = new Point(0,0);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $imagesize = $image->getSize();
        $this->start = new Point(
            max(0, round(($imagesize->getWidth() - $this->size->getWidth()) / 2)),
            max(0, round(($imagesize->getHeight() - $this->size->getHeight()) / 2))
        );
        return parent::apply($image);
    }
}
