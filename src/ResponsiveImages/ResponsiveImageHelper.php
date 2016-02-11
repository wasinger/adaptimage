<?php
namespace Wa72\AdaptImage\ResponsiveImages;

use Imagine\Image\ImagineInterface;
use Wa72\AdaptImage\ImageResizer;
use Wa72\AdaptImage\Output\OutputPathGeneratorInterface;

/**
 * This class is the main entry point for working with responsive images. It needs an object implementing
 * ResponsiveImageRouterInterface for finding image files given by its URL in the filesystem and for generating
 * links to resized images.
 *
 * Next, you need to add "classes" for responsive images via the addClass() method. Classes share the same "sizes"
 * attribute and the possible widths for image versions.
 *
 * Then you will be able to get a ResponsiveImage object describing an responsive image
 * via the getResponsiveImage() method.
 *
 */
class ResponsiveImageHelper
{
    /**
     * @var ResponsiveImageClass[]
     */
    protected $classes = [];

    /**
     * @var ResponsiveImageRouterInterface
     */
    protected $router;

    /**
     * @var ImageResizer
     */
    protected $resizer;

    /**
     *
     * @param ResponsiveImageRouterInterface $router
     * @param ImagineInterface $imagine
     * @param OutputPathGeneratorInterface $output_path_generator
     */
    public function __construct(
        ResponsiveImageRouterInterface $router,
        $imagine = null,
        $output_path_generator = null
    )
    {
        $this->router = $router;
        if ($imagine instanceof ImagineInterface && $output_path_generator instanceof OutputPathGeneratorInterface) {
            $this->resizer = new ImageResizer($imagine, $output_path_generator);
        }
    }

    /**
     * Add a ResponsiveImageClass object to the list of responsive image classes
     *
     * @param string $name A name (identifier) for this class
     * @param ResponsiveImageClass $class The ResponsiveImageClass object
     */
    public function addClass($name, ResponsiveImageClass $class)
    {
        $this->classes[$name] = $class;
    }

    /**
     * Check whether $classname is the name of a defined image class
     *
     * @param string $classname
     * @return bool
     */
    public function isClassDefined($classname)
    {
        return array_key_exists($classname, $this->classes);
    }

    /**
     * Get a ResponsiveImageClass by it's name
     *
     * @param string $classname
     * @return ResponsiveImageClass
     */
    public function getClass($classname)
    {
        return $this->classes[$classname];
    }

    /**
     * Add srcset and sizes attributes to an img tag
     *
     * @param \DOMElement $img
     * @param string $class
     */
    public function makeImgElementResponsive(\DOMElement &$img, $class)
    {
        if ($img->tagName != 'img') throw new \InvalidArgumentException('$img must be an img element');
        $imageurl = $img->getAttribute('src');
            try {
                $ri = $this->getResponsiveImage($imageurl, $class);
                $default = $ri->getDefaultImageInfo();
                $img->setAttribute('src', $default->getUrl());
                $img->setAttribute('width', $default->getWidth());
                $img->setAttribute('height', $default->getHeight());
                $img->setAttribute('srcset', $ri->getSrcsetAttributeValue());
                $img->setAttribute('sizes', $ri->getSizesAttributeValue());
            } catch (\Exception $e) {};
    }

    /**
     * Get ResponsiveImage object for a given original image and responsive image class
     *
     * @param string $imageurl The URL of the original image
     * @param string $imageclass The name of the defined responsive image class to get data for
     * @return ResponsiveImage
     */
    public function getResponsiveImage($imageurl, $imageclass)
    {
        return new ResponsiveImage($this->router, $imageurl, $this->getClass($imageclass));
    }

    /**
     * Create the resized image versions for an image and a given image class
     *
     * @param $imageurl
     * @param $imageclass
     * @throws \Exception
     */
    public function createResizedImageVersions($imageurl, $imageclass)
    {
        if (!$this->resizer instanceof ImageResizer) {
            throw new \Exception('no ImageResizer available, set it using ResponsiveImageHelper::setResizer()');
        }
        $image = $this->getResponsiveImage($imageurl, $imageclass);
        $image->createResizedVersions($this->resizer);
    }

    /**
     * Set an ImageResizer instance for use in resizing functions
     *
     * @param ImageResizer $resizer
     * @return $this
     */
    public function setResizer(ImageResizer $resizer)
    {
        $this->resizer = $resizer;
        return $this;
    }
}