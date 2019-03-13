<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Filter\FilterInterface;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Wa72\AdaptImage\ImageResizeDefinition;

/**
 * Imagine Filter that resizes the given image keeping its aspect ratio
 *
 *
 * Class ImagineFilterResize
 * @package Wa72\AdaptImage
 */
class ProportionalResize implements FilterInterface, ResizingFilterInterface
{

    /**
     * Default scale algorithm
     *
     * @var string One of the ImageInterface::FILTER_* constants
     */
    static $default_scale_algorithm = ImageInterface::FILTER_UNDEFINED;

    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @var boolean If true, the smaller dimension is used for scaling (i.e. the resulting image will not fit into the bounding box)
     */
    private $min;

    /**
     * @var boolean
     */
    private $upscale;

    /**
     * @var string The algorithm used for scaling, one of the ImageInterface::FILTER_* constants
     */
    private $filter;

    /**
     * @param int $width The desired new width.
     * @param int $height The desired new height. If INF or PHP_INT_MAX or ImageResizeDefinition::HEIGHT_UNRESTRICTED, it will not be restricted.
     * @param bool $min If true, the smaller dimension is used for scaling
     *                      (i.e. the resulting image will not fit into the bounding box)
     * @param bool $upscale Should the image be upscaled if it is smaller than new size?
     * @param string|null $scale_algorithm One of the ImageInterface::FILTER_* constants,
     *                                      defaults to ProportionalResize::$default_scale_algorithm
     */
    public function __construct($width, $height, $min = false, $upscale = false, $scale_algorithm = null)
    {
        if ($height === INF || $height === PHP_INT_MAX || $height === ImageResizeDefinition::HEIGHT_UNRESTRICTED) {
            if ($min) {
                throw new \InvalidArgumentException('When $height is not limited, $min must be false');
            }
            $height = ImageResizeDefinition::HEIGHT_UNRESTRICTED; // set $height to ImageResizeDefinition::HEIGHT_UNRESTRICTED as an integer marker for infinity
        }
        $this->size = new Box($width, $height);
        $this->min = $min;
        $this->upscale = $upscale;
        $this->filter = ($scale_algorithm ?: static::$default_scale_algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $img_dimensions = $image->getSize();
        if ($img_dimensions == $this->size || (!$this->upscale && $this->size->contains($img_dimensions))) {
            return $image;
        }
        return $image->resize($this->calculateSize($img_dimensions), $this->filter);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSize(BoxInterface $size)
    {
        if ($this->size->getHeight() == ImageResizeDefinition::HEIGHT_UNRESTRICTED && $this->size->getWidth() >= $size->getWidth() && !$this->upscale) {
            // if height is ImageResizeDefinition::HEIGHT_UNRESTRICTED this means it is to be ignored
            return $size;
        } elseif ($size == $this->size || (!$this->upscale && $this->size->contains($size))) {
            return $size;
        }
        $ratios = array(
            $this->size->getWidth() / $size->getWidth(),
            $this->size->getHeight() / $size->getHeight()
        );
        if (
            $this->size->getHeight() == ImageResizeDefinition::HEIGHT_UNRESTRICTED
            || ($this->min == false && $ratios[0] <= $ratios[1])
            || ($this->min == true && $ratios[0] >= $ratios[1])
        ) {
            $method = 'widen';
            $parameter = $this->size->getWidth();
        } else {
            $method = 'heighten';
            $parameter = $this->size->getHeight();
        }
        return call_user_func(array($size, $method), $parameter);
    }

    /**
     * Set the algorithm used for scaling
     *
     * @param $algorithm string One of the ImageInterface::FILTER_* constants
     */
    public function setScaleAlgorithm($algorithm)
    {
        $this->filter = $algorithm;
    }
}