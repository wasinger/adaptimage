<?php
namespace Wa72\AdaptImage;

use Imagine\Filter\FilterInterface;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Wa72\AdaptImage\ImagineFilter\CropCenter;
use Wa72\AdaptImage\ImagineFilter\ProportionalResize;
use Wa72\AdaptImage\ImagineFilter\ResizingFilterInterface;

/**
 * Class ImageResizeDefinition
 *
 * An object describing a size an image should be scaled to (and optionally the scaling mode and algorithm,
 * plus additional Imagine\Filter\FilterInterface filters to be applied when scaling)
 * that creates an Imagine\Filter\Transformation object to execute the defined scaling operation.
 *
 * @package Wa72\AdaptImage
 */
class ImageResizeDefinition {

    const MODE_MAX = 'max';
    const MODE_MIN = 'min';
    const MODE_CROP = 'crop';

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string one of the ImageResizeDefinition::MODE_* constants, i.e. 'max', 'min', or 'crop'
     */
    protected $mode;

    /**
     * @var boolean
     */
    protected $upscale;

    /**
     * @var Transformation
     */
    protected $transformation;

    /**
     * @var ProportionalResize
     */
    protected $resizefilter;

    /**
     * @param int $width The width of the new size
     * @param int $height   The height of the new size.
     *                      If set to 0 (default), it will be set to the same value as width.
     *                      If set to INF, it will not be restricted.
     * @param string $mode one of the ImageResizeDefinition::MODE_* constants, i.e. 'max', 'min', or 'crop'
     * @param bool $upscale Should the image be upscaled if it is smaller than the new size?
     * @param FilterInterface[] $filters Additional Filters to apply, e.g. for sharpening
     * @param string|null $scale_algorithm One of the ImageInterface::FILTER_* constants, defaults to ProportionalResize::$default_scale_algorithm
     */
    public function __construct($width, $height = 0, $mode = ImageResizeDefinition::MODE_MAX, $upscale = false, $filters = array(), $scale_algorithm = null)
    {
        $height = $height ?: $width; // if height is 0 (or not set), it defaults to $width
        if ($mode != ImageResizeDefinition::MODE_MAX && $height === INF) {
            throw new \InvalidArgumentException('Unlimited height is only allowed in "max" mode');
        }
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('width and height must be greater than 0');
        }
        $this->width = $width;
        $this->height =$height;
        $this->mode = $mode;
        $this->upscale = $upscale;
        $this->transformation = new Transformation();
        $this->resizefilter = new ProportionalResize(
            $width,
            $height,
            ($mode == ImageResizeDefinition::MODE_MIN || $mode == ImageResizeDefinition::MODE_CROP),
            $upscale,
            $scale_algorithm
        );
        $this->transformation->add($this->resizefilter);
        if ($mode == ImageResizeDefinition::MODE_CROP) {
            $this->transformation->add(new CropCenter(new Box($width, $height)));
        }
        foreach ($filters as $filter) {
            $this->transformation->add($filter);
        }
    }

    /**
     * Return the calculated new size for the resized image
     *
     * @param BoxInterface $size The original image size
     * @return BoxInterface The size after resizing
     */
    public function calculateSize(BoxInterface $size)
    {
        /** @var FilterInterface[] $filters */
        $filters = $this->transformation->getFilters();
        foreach ($filters as $filter) {
            if ($filter instanceof ResizingFilterInterface) {
                $size = $filter->calculateSize($size);
            }
        }
        return $size;
    }

    /**
     * @param FilterInterface $filter
     * @param int $priority
     */
    public function addFilter(FilterInterface $filter, $priority = 0)
    {
        $this->transformation->add($filter, $priority);
    }

    /**
     * Get the filter stack (the Imagine\Filter\Transformation) to execute the resize operation
     *
     * @return Transformation
     */
    public function getTransformation()
    {
        return $this->transformation;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
}