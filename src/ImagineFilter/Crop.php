<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Image\ImageInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Filter\FilterInterface;

/**
 * A crop filter that is optionally able to upsize the image if it is smaller than the desired crop size
 */
class Crop implements FilterInterface, ResizingFilterInterface
{
    /**
     * @var Point
     */
    protected $start;

    /**
     * @var BoxInterface
     */
    protected $size;

    /**
     * @var bool
     */
    protected $upscale;

    /**
     * Constructs a Crop filter with a starting point and crop width and height values
     *
     * @param Point $start
     * @param BoxInterface $size
     * @param bool $upscale If true, the image will be upscaled when it is smaller than the crop size
     */
    public function __construct(Point $start, BoxInterface $size, $upscale = false)
    {
        $this->start = $start;
        $this->size  = $size;
        $this->upscale = $upscale;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $imagesize = $image->getSize();
        if (!$this->upscale && !$this->isImageBigEnough($imagesize)) {
            return $image;
        } elseif ($this->upscale && !$this->isImageBigEnough($imagesize)) {
            $upscalefilter = new ProportionalResize($this->size, true, true);
            $image = $upscalefilter->apply($image);
            $imagesize = $image->getSize();
            $start = new Point(
                max(0, round(($imagesize->getWidth() - $this->size->getWidth()) / 2)),
                max(0, round(($imagesize->getHeight() - $this->size->getHeight()) / 2))
            );
        } else {
            $start = $this->start;
        }
        return $image->crop($start, $this->size);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSize(BoxInterface $size)
    {
        if (!$this->upscale && !$this->isImageBigEnough($size)) {
            return $size;
        } else {
            return $this->size;
        }
    }

    protected function isImageBigEnough(BoxInterface $imagesize)
    {
        if ($imagesize->getWidth() < $this->size->getWidth() || $imagesize->getHeight() < $this->size->getHeight()) {
            return false;
        } else {
            return true;
        }
    }

}
