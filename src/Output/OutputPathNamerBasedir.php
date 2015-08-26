<?php
namespace Wa72\AdaptImage\Output;

use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\ImagineFilter\FilterChain;

/**
 * Class OutputPathNamerBasedir is an OutputPathNamerInterface instance that stores the resulting files
 * in subdirectories of one common basedir.
 *
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
     * @param string $extension The file extension, without the dot (e.g. "jpg")
     * @param FilterChain|null $additional_transformation
     * @return string The pathname of the output image file
     */
    public function getOutputPathname(
        ImageFileInfo $input_image,
        ImageResizeDefinition $image_resize_definition,
        $extension,
        $additional_transformation = null
    )
    {
        if (count($image_resize_definition->getPostTransformation())) {
            $additional_transformation = ($additional_transformation instanceof FilterChain ? $additional_transformation : new FilterChain());
            $additional_transformation->append($image_resize_definition->getPostTransformation());
        }

        if ($additional_transformation instanceof FilterChain) {
            $additional_transformation_hash = md5(serialize($additional_transformation->getFilters()));
            $cachename = md5($input_image->getPathname() . $additional_transformation_hash) . '.' . $extension;
        } else {
            $cachename = md5($input_image->getPathname()) . '.' . $extension;
        }

        if (isset($this->filterhashes[$image_resize_definition])) {
            $transformation_hash = $this->filterhashes[$image_resize_definition];
        } else {
            $transformation_hash = md5(serialize($image_resize_definition->getResizeTransformation()->getFilters()));
            $this->filterhashes[$image_resize_definition] = $transformation_hash;
        }

        $cachepath = $this->basedir
            . DIRECTORY_SEPARATOR . $transformation_hash
            . DIRECTORY_SEPARATOR . $cachename;
        return $cachepath;
    }

}