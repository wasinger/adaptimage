<?php
namespace Wa72\AdaptImage;

use Imagine\Filter\FilterInterface;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Wa72\AdaptImage\ImagineFilter\CropCenter;
use Wa72\AdaptImage\ImagineFilter\FilterChain;
use Wa72\AdaptImage\ImagineFilter\ProportionalResize;
use Wa72\AdaptImage\Output\OutputTypeMap;

/**
 * Class ImageResizeDefinition
 *
 * An object describing a size an image should be scaled to (and optionally the scaling mode and algorithm,
 * plus additional Imagine\Filter\FilterInterface filters to be applied when scaling)
 * that creates an FilterChain object to execute the defined scaling operation.
 *
 */
class ImageResizeDefinition {

    const MODE_MAX = 'max';
    const MODE_MIN = 'min';
    const MODE_CROP = 'crop';

    const HEIGHT_UNRESTRICTED = 999999; // used to be INF; setting to 999999 is a workaround for a bug #705 in Imagine

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
     * @var FilterChain
     */
    protected $resize_transformation;

    /**
     * @var FilterChain
     */
    protected $post_transformation;

    /**
     * @var ProportionalResize
     */
    protected $resizefilter;

    /** @var  OutputTypeMap */
    protected $outputTypeMap;

    /**
     * @param int $width The width of the new size
     * @param int $height   The height of the new size.
     *                      If set to 0 (default), it will be set to the same value as width.
     *                      If set to ImageResizeDefinition::HEIGHT_UNRESTRICTED or INF or PHP_INT_MAX, height will not be restricted.
     * @param string $mode one of the ImageResizeDefinition::MODE_* constants, i.e. 'max', 'min', or 'crop'
     * @param bool $upscale Should the image be upscaled if it is smaller than the new size?
     * @param string|null $scale_algorithm One of the ImageInterface::FILTER_* constants, defaults to ProportionalResize::$default_scale_algorithm
     */
    public function __construct($width, $height = 0, $mode = ImageResizeDefinition::MODE_MAX, $upscale = false, $scale_algorithm = null)
    {
        $height = $height ?: $width; // if height is 0 (or not set), it defaults to $width
        if ($height === ImageResizeDefinition::HEIGHT_UNRESTRICTED || $height === \INF || $height === \PHP_INT_MAX) {
            if ($mode != ImageResizeDefinition::MODE_MAX) {
                throw new \InvalidArgumentException('Unlimited height is only allowed in "max" mode');
            } else {
                $height = ImageResizeDefinition::HEIGHT_UNRESTRICTED;
            }
        }
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('width and height must be greater than 0');
        }
        $this->width = $width;
        $this->height = $height;
        $this->mode = $mode;
        $this->upscale = $upscale;
        $this->resize_transformation = new FilterChain();
        $this->resizefilter = new ProportionalResize(
            $width,
            $height,
            ($mode == ImageResizeDefinition::MODE_MIN || $mode == ImageResizeDefinition::MODE_CROP),
            $upscale,
            $scale_algorithm
        );
        $this->resize_transformation->add($this->resizefilter);
        if ($mode == ImageResizeDefinition::MODE_CROP) {
            $this->resize_transformation->add(new CropCenter(new Box($width, $height)));
        }
        $this->post_transformation = new FilterChain();
        $this->outputTypeMap = new OutputTypeMap();
    }

    /**
     * Return the calculated new size for the resized image
     *
     * @param BoxInterface $size The original image size
     * @return BoxInterface The size after resizing
     */
    public function calculateSize(BoxInterface $size)
    {
        return $this->resize_transformation->calculateSize($size);
    }

    /**
     * add a filter to be executed when resizing the image, e.g. for sharpening
     *
     * This filter will be executed after the resizing operation,
     * but only if the image size has really changed. It will be skipped
     * if no resizing of the image is needed. Use it for adding Sharpen or UnsharpMask filters
     * that should be executed only after downscaling an image.
     *
     * @param FilterInterface $filter
     * @param int $priority
     * @return $this
     */
    public function addFilter(FilterInterface $filter, $priority = 0)
    {
        $this->resize_transformation->add($filter, $priority);
        return $this;
    }

    /**
     * add a post-processing filter to be always executed
     *
     * This filter will ALWAYS be executed no matter whether the image has
     * actually been resized or not. Usefull e.g. for Strip or Monochrome filter.
     *
     * @param FilterInterface $filter
     * @param int $priority
     * @return $this
     */
    public function addPostFilter(FilterInterface $filter, $priority = 0)
    {
        $this->post_transformation->add($filter, $priority);
        return $this;
    }

    /**
     * Set the algorithm used for scaling
     *
     * @param string $algorithm One of the ImageInterface::FILTER_* constants
     * @return $this
     */
    public function setScaleAlgorithm($algorithm)
    {
        $this->resizefilter->setScaleAlgorithm($algorithm);
        return $this;
    }

    /**
     * Get the filter stack to execute the resize operation
     *
     * @return FilterChain
     */
    public function getResizeTransformation()
    {
        return $this->resize_transformation;
    }

    /**
     * Get the filter stack to execute transformations after resizing
     *
     * @return FilterChain
     */
    public function getPostTransformation()
    {
        return $this->post_transformation;
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

    /**
     * @return OutputTypeMap
     */
    public function getOutputTypeMap()
    {
        return $this->outputTypeMap;
    }

    /**
     * @param OutputTypeMap $outputTypeMap
     * @return ImageResizeDefinition
     */
    public function setOutputTypeMap(OutputTypeMap $outputTypeMap)
    {
        $this->outputTypeMap = $outputTypeMap;
        return $this;
    }

    /**
     * Create an ImageResizeDefinition instance
     *
     * @param int $width The width of the new size
     * @param int $height   The height of the new size.
     *                      If set to 0 (default), it will be set to the same value as width.
     *                      If set to ImageResizeDefinition::HEIGHT_UNRESTRICTED, height will not be restricted.
     * @param string $mode one of the ImageResizeDefinition::MODE_* constants, i.e. 'max', 'min', or 'crop'
     * @param bool $upscale Should the image be upscaled if it is smaller than the new size?
     * @return ImageResizeDefinition
     */
    static function create($width, $height = 0, $mode = ImageResizeDefinition::MODE_MAX, $upscale = false)
    {
        return new static($width, $height, $mode, $upscale);
    }
}