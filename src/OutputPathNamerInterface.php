<?php
namespace Wa72\AdaptImage;
use Imagine\Filter\Transformation;

/**
 * Interface OutputPathNamerInterface computes the output path and filename for a given original image file
 * and an ImageResizeDefinition object
 *
 * @package Wa72\AdaptImage
 */
interface OutputPathNamerInterface {
    /**
     * @param ImageFileInfo $input_image
     * @param ImageResizeDefinition $image_resize_definition
     * @param FilterChain|null $additional_transformation
     * @return string The pathname of the output image file
     */
    public function getOutputPathname(
        ImageFileInfo $input_image,
        ImageResizeDefinition $image_resize_definition,
        $additional_transformation = null
    );
}