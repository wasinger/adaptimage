<?php
namespace Wa72\AdaptImage;

use Imagine\Filter\Basic\Strip;
use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;

/**
 * Class ThumbnailGenerator
 *
 * Creates and caches thumbnails for images
 *
 * @package Wa72\AdaptImage
 */
class ThumbnailGenerator {
    /** @var  CachingImageTransformer */
    protected $transformer;

    /**
     * @param ImagineInterface $imagine
     * @param string $cache_dir     The base directory where the resized images should be cached.
     * @param int $width            The width of the thumbnail
     * @param int $height           The height of the thumbnail
     * @param string $mode          One of ImageInterface::THUMBNAIL_INSET or ImageInterface::THUMBNAIL_OUTBOUND
     * @param FilterInterface[] $filters    Additional Filters to apply, e.g. for sharpening
     */
    public function __construct(ImagineInterface $imagine, $cache_dir, $width, $height, $mode = ImageInterface::THUMBNAIL_INSET, $filters = array())
    {
        $mode = ($mode == ImageInterface::THUMBNAIL_INSET ? ImageResizeDefinition::MODE_MAX : ImageResizeDefinition::MODE_CROP);
        $ird = new ImageResizeDefinition($width, $height, $mode, false, $filters);
        $transformation = $ird->getTransformation();
        $transformation->add(new Strip());
        $this->transformer = new CachingImageTransformer($imagine, $transformation, $cache_dir);
    }

    /**
     * Get a thumbnail for an image
     *
     * If the thumbnail is already generated and cached, return the cached thumbnail. If the thumbnail is not yet
     * in the cache, the thumbnail will be generated and cached, but only if the first param $really_do_it
     * is TRUE. If it is FALSE, an ImageFileInfo is returned that contains the resulting size, pathname, and type of
     * the thumbnail, though this file does not exist (yet).
     *
     * @param bool $really_do_it    If false, don't really create thumbnail if it isn't in cache yet
     *                              but only calculate the resulting ImageFileInfo (size, type, and pathname)
     * @param ImageFileInfo $image  The image for which the thumbnail is to be generated
     * @return ImageFileInfo        Information about the thumbnail such as pathname, type, and size
     * @throws \Exception           If the thumbnail can not be generated
     */
    public function thumbnail($really_do_it, ImageFileInfo $image)
    {
        return $this->transformer->transform($image, $really_do_it);
    }

}