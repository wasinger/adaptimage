<?php
namespace Wa72\AdaptImage\ResponsiveImages;

use Imagine\Filter\FilterInterface;
use Wa72\AdaptImage\Exception\WidthNotAllowedException;
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\Output\OutputTypeMap;

/**
 * ResponsiveImageClass represents a predefined "class" of responsive images in HTML
 * that all share a common "sizes" attribute and available image widths.
 *
 * It holds information about which widths are available for an image of this class
 * and the "sizes" attribute needed for responsive img tags. It creates ImageResizeDefinition objects
 * for the defined widths.
 *
 * Usage: Create an instance of this class using "new ResponsiveImageClass(...)" and add it to a
 * ResponsiveImageHelper instance using the ResponsiveImageHelper::addClass() method.
 *
 */
class ResponsiveImageClass
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int[]
     */
    protected $available_image_widths = [];

    /**
     * @var ImageResizeDefinition[]
     */
    protected $irds = [];

    /**
     * @var string
     */
    protected $html_sizes_attribute;

    /**
     * @var int
     */
    protected $default_width;

    /**
     * ResponsiveImageClass constructor.
     *
     * @param string $name A name (identifier) for this class of responsive images
     * @param int[] $available_image_widths List of available image widths
     *
     * @param string $html_sizes_attribute The "sizes" html attribute for img tags of this class
     *
     * @param Callable|int $height_constraint How the max height of the generated images is limited, possible values:
     *                              0: Max. height = width
     *                              INF: Height is unlimited
     *                              or a Callable accepting the width as parameter and returning the height
     *
     * @param int $default_width The default width of an image of this class,
     *                              defaults to the first value in $available_image_widths
     *
     * @param bool $upscale Whether the image should be upscaled if it's width is smaller than required width
     *                          default: false
     *
     * @param string|null $scale_algorithm One of the Imagine\ImageInterface::FILTER_* constants
     *                      defaults to Wa72\AdaptImage\ImagineFilter\ProportionalResize::$default_scale_algorithm
     *
     * @param FilterInterface[] $additional_filters Additional Imagine Filters to be applied when resizing,
     *                                              e.g. for sharpening. These filters are only applied when the
     *                                              image really gets resized, but not when the image already has
     *                                              the specified size.
     *
     * @param FilterInterface[] $post_filters Additional Imagine Filters to be always applied AFTER resizing, e.g.
     *                                          Strip() filters. These filters are ALWAYS applied, even if the image
     *                                          already has the specified size and does not get resized at all.
     *
     * @param OutputTypeMap|null $output_type_map Set an OutputTypeMap for file type conversion when resizing
     * @param string $mode Crop mode, one of the ImageResizeDefinition::MODE_xx constants, default: MODE_MAX
     */
    public function __construct(
        $name,
        $available_image_widths,
        $html_sizes_attribute,
        $height_constraint = INF,
        $default_width = 0,
        $upscale = false,
        $scale_algorithm = null,
        $additional_filters = [],
        $post_filters = [],
        $output_type_map = null,
        $mode = ImageResizeDefinition::MODE_MAX
    )
    {
        $this->name = $name;
        $this->available_image_widths = $available_image_widths;

        if ($height_constraint === 0
            || $height_constraint === INF
            || is_callable($height_constraint))
        {
            $height = $height_constraint;
        } else {
            throw new \InvalidArgumentException('height_constraint must be Callable or 0 or INF');
        }

        foreach ($available_image_widths as $width) {
            if (is_callable($height_constraint)) {
                $height = call_user_func($height_constraint, $width);
            }
            $this->irds[$width] = ImageResizeDefinition::create($width, $height, $mode, $upscale);
            if (count($additional_filters)) {
                foreach ($additional_filters as $filter) {
                    $this->irds[$width]->addFilter($filter);
                }
            }
            if (count($post_filters)) {
                foreach ($post_filters as $filter) {
                    $this->irds[$width]->addPostFilter($filter);
                }
            }
            if ($output_type_map instanceof OutputTypeMap) {
                $this->irds[$width]->setOutputTypeMap($output_type_map);
            }
        }

        $this->html_sizes_attribute = $html_sizes_attribute;

        if ($default_width > 0) {
            if (!in_array($default_width, $this->available_image_widths)) {
                throw new WidthNotAllowedException($default_width);
            }
            $this->default_width = $default_width;
        } else {
            $this->default_width = $available_image_widths[0];
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get all defined image widths
     *
     * @return int[]
     */
    public function getAvailableImageWidths()
    {
        return $this->available_image_widths;
    }

    /**
     * Get the default width
     *
     * @return int
     */
    public function getDefaultWidth()
    {
        return $this->default_width;
    }

    /**
     * Get the "sizes" attribute value for the HTML img tag
     *
     * @return string
     */
    public function getHtmlSizesAttribute()
    {
        return $this->html_sizes_attribute;
    }

    /**
     * Get an ImageResizeDefinition for a given width
     *
     * @param int $width
     * @return ImageResizeDefinition
     */
    public function getImageResizeDefinitionByWidth($width)
    {
        if (!key_exists($width, $this->irds)) {
            throw new WidthNotAllowedException($width);
        }
        return $this->irds[$width];
    }

    /**
     * Get ImageResizeDefinitions for all defined widths
     *
     * @return ImageResizeDefinition[]
     */
    public function getImageResizeDefinitions()
    {
        return $this->irds;
    }

    /**
     * Get the ImageResizeDefinition for the default width
     *
     * @return int
     */
    public function getDefaultImageResizeDefinition()
    {
        return $this->irds[$this->default_width];
    }

    /**
     * Check whether $width is a defined width for this class
     *
     * @param int $width
     * @return bool
     */
    public function hasWidth($width)
    {
        return in_array($width, $this->available_image_widths);
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
        foreach ($this->available_image_widths as $width) {
            $this->irds[$width]->addFilter($filter, $priority);
        }
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
        foreach ($this->available_image_widths as $width) {
            $this->irds[$width]->addPostFilter($filter, $priority);
        }
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
        foreach ($this->available_image_widths as $width) {
            $this->irds[$width]->setScaleAlgorithm($algorithm);
        }
        return $this;
    }

    /**
     * @param OutputTypeMap $outputTypeMap
     * @return ImageResizeDefinition
     */
    public function setOutputTypeMap(OutputTypeMap $outputTypeMap)
    {
        foreach ($this->available_image_widths as $width) {
            $this->irds[$width]->setOutputTypeMap($outputTypeMap);
        }
        return $this;
    }
}