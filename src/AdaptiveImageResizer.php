<?php
namespace Wa72\AdaptImage;

use Imagine\Image\ImagineInterface;
use Wa72\AdaptImage\Output\OutputPathNamerBasedir;
use Wa72\AdaptImage\Output\OutputPathNamerInterface;

/**
 * Class AdaptiveImageResizer
 *
 * Creates and caches images resized to some predefined sizes
 *
 * @package Wa72\AdaptImage
 */
class AdaptiveImageResizer
{
    /** @var  ImageResizeDefinition[] */
    protected $image_resize_definitions;

    /**
     * @var ImageResizer
     */
    protected $resizer;

    /**
     * @param ImagineInterface $imagine
     * @param OutputPathNamerInterface $output_path_namer
     * @param ImageResizeDefinition[] $image_resize_definitions The predefined image sizes to which images may be scaled
     */
    public function __construct(ImagineInterface $imagine, OutputPathNamerInterface $output_path_namer, $image_resize_definitions = array())
    {
        foreach ($image_resize_definitions as $ird) {
            /** @var ImageResizeDefinition $ird */
            $this->image_resize_definitions[$ird->getWidth()] = $ird;
        }
        ksort($this->image_resize_definitions);
        $this->resizer = new ImageResizer($imagine, $output_path_namer);
    }

    /**
     * Add another allowed image size
     *
     * @param ImageResizeDefinition $ird
     */
    public function addImageSizeDefinition(ImageResizeDefinition $ird)
    {
        $this->image_resize_definitions[$ird->getWidth()] = $ird;
        ksort($this->image_resize_definitions);
    }

    /**
     * Get nearest available ImageSizeDefinition for given screen width
     *
     * @param int $width
     * @param boolean $fit_in_width If false, returns ImageSizeDefinition greater than width; if true, returns ImageSizeDefinition smaller than width
     * @return ImageResizeDefinition|null Matching ImageSizeDefinition or NULL if no ImageSizeDefinitions available
     */
    public function getImageResizeDefinitionForWidth($width, $fit_in_width = false)
    {
        if (empty($this->image_resize_definitions)) { // no image sizes defined
            return null;
        }

        if ($fit_in_width) {
            foreach ($this->image_resize_definitions as $w => $ird) {
                if ($w > $width && isset($prev_ird)) {
                    return $prev_ird;
                } elseif ($w >= $width) {
                    return $ird; // return smallest possible value even if it is greater than required
                }
                $prev_ird = $ird;
            }
            return $ird; // return greatest available value
        } else {
            foreach ($this->image_resize_definitions as $w => $ird) {
                if ($w >= $width) {
                    return $ird;
                }
            }
            return $ird; // return greatest available value
        }
    }

    /**
     * Get the image resized to the pre-defined size that is nearest to $width.
     *
     * If the resized image is already generated and cached, return the cached image. If the image is not yet
     * in the cache, the resized image will be generated and cached, but only if the first param $really_do_it
     * is TRUE. If it is FALSE, an ImageFileInfo is returned that contains the resulting size, pathname, and type of
     * the resized image, though this image file does not exist (yet).
     *
     * @param bool $really_do_it    If FALSE, don't really resize the image (if it isn't in cache yet)
     *                              but only calculate the resulting ImageFileInfo (size, type, and pathname)
     * @param ImageFileInfo $image  The image to be resized
     * @param int $width            The desired width of the resulting image; if this width does not match any
     *                              of the pre-defined ImageResizeDefinitions an image is returned that is either
     *                              wider or narrower than width, depending on the next parameter
     * @param bool $fit_in_width    If false, generates image greater than width;
     *                              if true, generates image smaller than width
     *                              (if there is not ImageResizeDefinition exactly matching width)
     * @return ImageFileInfo        Information about the resized image such as pathname, type, and size
     * @throws \Exception           If the resized image can not be generated
     */
    public function resize($really_do_it, ImageFileInfo $image, $width, $fit_in_width = false)
    {
        $ird = $this->getImageResizeDefinitionForWidth($width, $fit_in_width);
        if ($ird === null) {
            throw new \Exception('No ImageResizeDefinitions available');
        }
        return $this->resizer->resize($ird, $image, $really_do_it);
    }

    /**
     * Factory function for creating an AdaptiveImageResizer
     * for an array of allowed image widths (and unlimited height)
     *
     * @param int[] $widths Array of allowed image width values in pixels
     * @param ImagineInterface $imagine    The Imagine instance for scaling the images.
     * @param OutputPathNamerInterface|null $output_path_namer  The OutputPathNamer for generating the names
     *                                                          of the cached files. If none is provided, a new
     *                                                          instance of OutputPathNamerBasedir will be created
     *                                                          with directory sys_get_temp_dir() . '/ai_cache'
     * @return AdaptiveImageResizer
     */
    static public function create($widths, $imagine, $output_path_namer = null)
    {
        if (!($output_path_namer instanceof OutputPathNamerInterface)) {
            $output_path_namer = new OutputPathNamerBasedir(sys_get_temp_dir() . '/ai_cache');
        }
        $irds = array();
        foreach ($widths as $width) {
            $irds[] = ImageResizeDefinition::create($width, INF);
        }
        return new self($imagine, $output_path_namer, $irds);
    }
}