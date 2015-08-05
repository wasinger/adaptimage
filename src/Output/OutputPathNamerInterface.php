<?php
namespace Wa72\AdaptImage\Output;

use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\ImagineFilter\FilterChain;

/**
 * Interface OutputPathNamerInterface computes the output path and filename for a given original image file
 * and an ImageResizeDefinition object
 *
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