<?php
namespace Wa72\AdaptImage;
use Imagine\Filter\Transformation;

/**
 * Class OutputPathNamerBasedir is an OutputPathNamerInterface instance that stores the resulting files
 * in subdirectories of one common basedir.
 *
 *
 * @package Wa72\AdaptImage
 */
class OutputPathNamerBasedir implements OutputPathNamerInterface
{
    /**
     * @var string
     */
    protected $basedir;

    /**
     * @var \SplObjectStorage
     */
    private $filterhashes;

    /**
     * @param string $basedir The base directory where the
     */
    public function __construct($basedir)
    {
        $this->basedir = $basedir;
        $this->filterhashes = new \SplObjectStorage();
    }

    /**
     * @param ImageFileInfo $input_image
     * @param ImageResizeDefinition $image_resize_definition
     * @param \Imagine\Filter\Transformation|null $additional_transformation
     * @return string The pathname of the output image file
     */
    public function getOutputPathname(
        ImageFileInfo $input_image,
        ImageResizeDefinition $image_resize_definition,
        $additional_transformation = null
    )
    {
        $imagetype = $input_image->getImagetype();
        $extension = image_type_to_extension($imagetype, false);

        if ($additional_transformation instanceof Transformation) {
            $additional_transformation_hash = md5(serialize($additional_transformation->getFilters()));
            $cachename = md5($input_image->getPathname() . $additional_transformation_hash) . '.' . $extension;
        } else {
            $cachename = md5($input_image->getPathname()) . '.' . $extension;
        }

        if (isset($this->filterhashes[$image_resize_definition])) {
            $transformation_hash = $this->filterhashes[$image_resize_definition];
        } else {
            $transformation_hash = md5(serialize($image_resize_definition->getTransformation()->getFilters()));
            $this->filterhashes[$image_resize_definition] = $transformation_hash;
        }

        $cachepath = $this->basedir
            . DIRECTORY_SEPARATOR . $transformation_hash
            . DIRECTORY_SEPARATOR . $cachename;
        return $cachepath;
    }

}