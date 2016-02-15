<?php
namespace Wa72\AdaptImage\ResponsiveImages;

use Imagine\Image\Box;
use Wa72\AdaptImage\Exception\WidthNotAllowedException;
use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ImageResizer;
use Wa72\AdaptImage\WebImageInfo;

/**
 * This class represents a "responsive image" with multiple widths
 * as described by an HTML "img" tag with "srcset" and "sizes" attributes
 *
 * Its main purpose is to generate the value of the "srcset" attribute from the available image widths,
 * and the resized image versions.
 *
 */
class ResponsiveImage
{
    /**
     * @var string
     */
    protected $original_image_url;

    /**
     * @var ImageFileInfo
     */
    protected $original_ifi;
    /**
     * @var ResponsiveImageClass
     */
    protected $class;
    /**
     * @var WebImageInfo[]
     */
    protected $versions;

    /**
     * ResponsiveImage constructor.
     *
     * @param ResponsiveImageRouterInterface $router    The "router" for generating image URLs
     * @param string $original_image_url    The URL of the original image, typically relative to the web root dir
     * @param ResponsiveImageClass $class   The ResponsiveImageClass describing the available responsive image sizes
     */
    function __construct(ResponsiveImageRouterInterface $router, $original_image_url, ResponsiveImageClass $class)
    {
        $this->original_image_url = $original_image_url;
        $this->class = $class;

        $this->original_ifi = $router->getOriginalImageFileInfo($original_image_url);

        $imgdata = [];
        $irds = $class->getImageResizeDefinitions();
        $prevwidth = 0;
        foreach ($irds as $width => $ird) {
            $ii = $ird->calculateSize(new Box($this->original_ifi->getWidth(), $this->original_ifi->getHeight()));
            if ($ii->getWidth() != $prevwidth) { // avoid duplicates
                $imgdata[$width] = new WebImageInfo(
                    $router->generateUrl($original_image_url, $class->getName(), $width),
                    $ii->getWidth(),
                    $ii->getHeight(),
                    $this->original_ifi->getMimetype()
                );
            }
            $prevwidth = $ii->getWidth();
        }
        $this->versions = $imgdata;
    }

    /**
     * Get the value for the "srcset" HTML attribute
     *
     * @return string
     */
    public function getSrcsetAttributeValue()
    {
        $srcset = [];
        foreach ($this->versions as $id) {
            $srcset[] = sprintf('%s %sw', $id->getUrl(), $id->getWidth());
        }
        return join(', ', $srcset);
    }

    /**
     * Get the value for the "sizes" HTML attribute
     *
     * @return string
     */
    public function getSizesAttributeValue()
    {
        return $this->class->getHtmlSizesAttribute();
    }

    /**
     * Get WebImageInfo objects for all available image sizes
     *
     * @return \Wa72\AdaptImage\WebImageInfo[]
     */
    public function getVersionsImageInfos()
    {
        return $this->versions;
    }

    /**
     * Get WebImageInfo object for the default image size
     *
     * @return \Wa72\AdaptImage\WebImageInfo
     */
    public function getDefaultImageInfo()
    {
        return $this->versions[$this->class->getDefaultWidth()];
    }

    /**
     * Really create all resized versions of this image
     *
     * @param ImageResizer $resizer
     * @throws \Exception
     */
    public function createResizedVersions(ImageResizer $resizer)
    {
        foreach ($this->class->getImageResizeDefinitions() as $ird) {
            $resizer->resize($ird, $this->original_ifi, true);
        }
    }

    /**
     * Resize the image to a defined width and return the corresponding ImageFileInfo object
     *
     * @param ImageResizer $resizer
     * @param int $width The desired width; it must be a width that is availaible in the image class
     * @return ImageFileInfo The ImageFileInfo object pointing to the resized image file
     * @throws \Exception
     */
    public function getResizedVersion(ImageResizer $resizer, $width)
    {
        if (!$this->class->hasWidth($width)) {
            throw new WidthNotAllowedException($width);
        }
        return $resizer->resize(
            $this->class->getImageResizeDefinitionByWidth($width),
            $this->original_ifi,
            true
        );
    }
}
