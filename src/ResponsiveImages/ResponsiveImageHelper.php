<?php
namespace Wa72\AdaptImage\ResponsiveImages;

use Imagine\Filter\FilterInterface;
use Imagine\Image\ImagineInterface;
use Wa72\AdaptImage\Exception\ImageClassNotRegisteredException;
use Wa72\AdaptImage\Exception\ImageFileNotFoundException;
use Wa72\AdaptImage\Exception\ImageResizingFailedException;
use Wa72\AdaptImage\Exception\WidthNotAllowedException;
use Wa72\AdaptImage\ImageResizer;
use Wa72\AdaptImage\ImagineFilter\FilterChain;
use Wa72\AdaptImage\Output\OutputPathGeneratorInterface;
use Wa72\AdaptImage\Output\OutputTypeMap;
use Wa72\AdaptImage\WebImageInfo;

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
     * @var int[]
     */
    protected $widths = [];

    /**
     * @var FilterChain
     */
    protected $default_filters;

    /**
     * @var FilterChain
     */
    protected $default_post_filters;

    /**
     * @var OutputTypeMap
     */
    protected $default_output_type_map;

    /**
     * @var string One of the ImageInterface::FILTER_* constants
     */
    protected $default_scaling_algorithm;

    /**
     * @var bool
     */
    protected $classes_added = false;
    
    
    protected $supported_types = ['image/jpeg', 'image/png', 'image/gif'];

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
     * Add a ResponsiveImageClass object to the registered responsive image classes
     *
     * @param ResponsiveImageClass $class The ResponsiveImageClass object
     * @throws \Exception
     */
    public function addClass(ResponsiveImageClass $class)
    {
        $this->classes_added = true;
        if (array_key_exists($class->getName(), $this->classes)) {
            throw new \Exception(sprintf('A responsive image class with name "%s" is already registered', $class->getName()));
        }
        if ($this->default_filters instanceof FilterInterface) {
            $class->addFilter($this->default_filters);
        }
        if ($this->default_post_filters instanceof FilterInterface) {
            $class->addPostFilter($this->default_post_filters);
        }
        if ($this->default_scaling_algorithm != '') {
            $class->setScaleAlgorithm($this->default_scaling_algorithm);
        }
        if ($this->default_output_type_map instanceof OutputTypeMap) {
            $class->setOutputTypeMap($this->default_output_type_map);
        }
        $this->classes[$class->getName()] = $class;
    }

    /**
     * Check whether $classname is the name of a defined image class
     *
     * @param string $classname
     * @return bool
     */
    public function isClassDefined($classname)
    {
        return (string) $classname != '' && array_key_exists($classname, $this->classes);
    }

    /**
     * Get a ResponsiveImageClass by it's name
     *
     * @param string $classname
     * @return ResponsiveImageClass
     */
    public function getClass($classname)
    {
        if ((string) $classname == '' || !array_key_exists($classname, $this->classes)) {
            throw new ImageClassNotRegisteredException($classname);
        }
        return $this->classes[$classname];
    }

    /**
     * Add srcset and sizes attributes to an HTML "img" tag
     *
     * This method reads the image URL from the "src" attribute of an "img" element, calculates the URLs and dimensions of
     * the various available image sizes according to the given "responsive image class", and adds the "srcset" and
     * "sizes" attribute value.
     *
     * Optionally, it can change the "src" attribute value to the URL of the default image size, and set the
     * "width" and "height" attributes for the default image size.
     *
     * N.B. The URL given in the "src" attribute of the img element must be in a form the
     * ResponsiveImageRouterInterface object $this->router is able to convert to a local file path.
     * If the conversion fails, the method fails silently, i.e. the img element will not be changed.
     *
     * @param \DOMElement $img The "img" HTML element
     * @param string $class The name of an "responsive image class" registered via addClass()
     * @param bool $change_src_attr If true, set "src" attribute to the URL of the default image size
     * @param bool $add_default_dimension_attrs If true, add "width" and "height" attributes for the default image size
     * @throws \InvalidArgumentException If the DOMElement provided as $img parameter is not an "img" element
     * @throws ImageClassNotRegisteredException If the $class parameter is not a registerd image class name
     * @API
     */
    public function makeImgElementResponsive(
        \DOMElement &$img, 
        $class, 
        $change_src_attr = false, 
        $add_default_dimension_attrs = false
    )
    {
        if ($img->tagName != 'img') throw new \InvalidArgumentException('$img must be an "img" element');
        if ((string) $class == '' || !array_key_exists($class, $this->classes)) {
            throw new ImageClassNotRegisteredException($class);
        }
        $imageurl = $img->getAttribute('src');
        if ($imageurl != '') {
            try {
                $ri = $this->getResponsiveImage($imageurl, $class);
                $img->setAttribute('srcset', $ri->getSrcsetAttributeValue());
                $img->setAttribute('sizes', $ri->getSizesAttributeValue());
                $default = $ri->getDefaultImageInfo();
                if ($default instanceof WebImageInfo) {
                    if ($change_src_attr) {
                        $img->setAttribute('src', $default->getUrl());
                    }
                    if ($add_default_dimension_attrs) {
                        $img->setAttribute('width', $default->getWidth());
                        $img->setAttribute('height', $default->getHeight());
                    }
                }
            } catch (\Exception $e) {
            };
        }
    }

    /**
     * @param string $imageurl
     * @param string $imageclass
     * @param array $options ['attr' => [associative array of html attributes], 'add_default_dimension_attrs' => true|false]
     * @return string
     */
    public function getResponsiveHtmlImageTag(string $imageurl, string $imageclass, $options = [])
    {
        $options = array_replace([
            'attr' => [],
            'add_default_dimension_attrs' => false
        ], $options);
        
        $s = '<img';
        foreach ($options['attr'] as $name => $value) {
            $s .= ' ' . $name . '="' . \htmlspecialchars($value) . '"';
        }

        $show_dimensions = $options['add_default_dimension_attrs'];

        if ($imageurl != '') {
            try {
                $ri = $this->getResponsiveImage($imageurl, $imageclass);
                $default = $ri->getDefaultImageInfo();
                $s .= ' src="' . $default->getUrl() . '"';
                if ($show_dimensions) {
                    $s .= ' width="' . $default->getWidth() . '"';
                    $s .= ' height="' . $default->getHeight() . '"';
                }
                $s .= ' srcset="' . $ri->getSrcsetAttributeValue() . '"';
                $s .= ' sizes="' . $ri->getSizesAttributeValue() . '"';
            } catch (\Exception $e) {
                $s .= ' src="'.$imageurl.'"';
            }
        } else {
            $s .= ' src=""';
        }

        $s .= ' />';
        return $s;
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
        return new ResponsiveImage($this, $imageurl, $this->getClass($imageclass));
    }

    /**
     * Create the resized image versions for an image and a given image class
     *
     * @param string $imageurl
     * @param string $imageclass
     * @throws \Exception
     */
    public function createResizedImageVersions($imageurl, $imageclass)
    {
        if (!$this->resizer instanceof ImageResizer) {
            throw new \LogicException('no ImageResizer available, set it using ResponsiveImageHelper::setResizer()');
        }
        $image = $this->getResponsiveImage($imageurl, $imageclass);
        $image->createResizedVersions($this->resizer);
    }

    /**
     * @param string $imageurl
     * @param string $imageclass
     * @param int $width
     * @return \Wa72\AdaptImage\ImageFileInfo
     * @throws \LogicException If $this->imageresizer is not an instance of ImageResizer
     * @throws ImageClassNotRegisteredException If the image class is not known
     * @throws WidthNotAllowedException If the given width is not allowed in this class
     * @throws ImageFileNotFoundException If the original image is not found
     * @throws ImageResizingFailedException If resizing of the image failed
     */
    public function getResizedImageVersion($imageurl, $imageclass, $width)
    {
        $imageclass = $this->getClass($imageclass);
        if (!$this->resizer instanceof ImageResizer) {
            throw new \LogicException('no ImageResizer available, set it using ResponsiveImageHelper::setResizer()');
        }
        if (!$imageclass->hasWidth($width)) {
            throw new WidthNotAllowedException($width);
        }
        $image = $this->router->getOriginalImageFileInfo($imageurl);
        return $this->resizer->resize(
            $imageclass->getImageResizeDefinitionByWidth($width),
            $image,
            true
        );
    }

    /**
     * Set an ImageResizer instance for use in resizing functions
     *
     * @param ImageResizer $resizer
     * @return ResponsiveImageHelper $this
     */
    public function setResizer(ImageResizer $resizer)
    {
        $this->resizer = $resizer;
        return $this;
    }

    /**
     * Add additional default filters to be applied when resizing for all classes.
     * This setting will be passed to a ResponsiveImageClass object when it is added by the addClass() method
     *
     * @param FilterInterface $default_filter
     * @return ResponsiveImageHelper $this
     */
    public function addDefaultFilter(FilterInterface $default_filter)
    {
        if ($this->classes_added) {
            throw new \LogicException('Defaults must be set before image classes are added.');
        }
        if (!$this->default_filters instanceof FilterChain) {
            $this->default_filters = new FilterChain();
        }
        $this->default_filters->add($default_filter);
        return $this;
    }

    /**
     * Set default post filters for all image classes.
     * This setting will be passed to a ResponsiveImageClass object when it is added by the addClass() method
     *
     * @param \Imagine\Filter\FilterInterface $default_post_filter
     * @return ResponsiveImageHelper $this
     */
    public function addDefaultPostFilter(FilterInterface $default_post_filter)
    {
        if ($this->classes_added) {
            throw new \LogicException('Defaults must be set before image classes are added.');
        }
        if (!$this->default_post_filters instanceof FilterChain) {
            $this->default_post_filters = new FilterChain();
        }
        $this->default_post_filters->add($default_post_filter);
        return $this;
    }

    /**
     * Set a default OutputTypeMap for all image classes.
     * This setting will be passed to a ResponsiveImageClass object when it is added by the addClass() method
     *
     * @param OutputTypeMap $default_output_type_map
     * @return ResponsiveImageHelper $this
     */
    public function setDefaultOutputTypeMap(OutputTypeMap $default_output_type_map)
    {
        if ($this->classes_added) {
            throw new \LogicException('Defaults must be set before image classes are added.');
        }
        $this->default_output_type_map = $default_output_type_map;
        return $this;
    }

    /**
     * Set a default scaling algorithm for all image classes.
     * This setting will be passed to a ResponsiveImageClass object when it is added by the addClass() method
     *
     * @param string $default_scaling_algorithm One of the ImageInterface::FILTER_* constants
     * @return ResponsiveImageHelper $this
     */
    public function setDefaultScalingAlgorithm($default_scaling_algorithm)
    {
        if ($this->classes_added) {
            throw new \LogicException('Defaults must be set before image classes are added.');
        }
        $this->default_scaling_algorithm = $default_scaling_algorithm;
        return $this;
    }

    /**
     * @return array
     */
    public function getSupportedTypes()
    {
        return $this->supported_types;
    }

    /**
     * @param array $supported_types
     */
    public function setSupportedTypes($supported_types)
    {
        $this->supported_types = $supported_types;
    }
    
    public function supports($mime_type)
    {
        return in_array($mime_type, $this->getSupportedTypes());
    }

    /**
     * @return ResponsiveImageRouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param ResponsiveImageRouterInterface $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

}