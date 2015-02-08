<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Image\ImageInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Filter\FilterInterface;

/**
 * A crop filter that crops a specified box from the center of the image
 */
class CropCenter implements FilterInterface, ResizingFilterInterface
{
    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * Constructs a Crop filter with crop width and height values
     * The starting point will be calculated so that the given box is centered in the image
     *
     * @param BoxInterface   $size
     */
    public function __construct(BoxInterface $size)
    {
        $this->size  = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $imagesize = $image->getSize();
        if ($this->size->contains($imagesize)) {
            return $image;
        }
        $start = new Point(
            max(0, round(($imagesize->getWidth() - $this->size->getWidth()) / 2)),
            max(0, round(($imagesize->getHeight() - $this->size->getHeight()) / 2))
        );
        return $image->crop($start, $this->size);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSize(BoxInterface $size)
    {
        if ($this->size->contains($size)) {
            return $size;
        } else {
            return $this->size;
        }
    }

}
