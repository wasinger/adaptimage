<?php
namespace Wa72\AdaptImage\Output;

use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\ImagineFilter\FilterChain;

/**
 * Interface OutputPathGeneratorInterface computes the output path and filename for a given original image file
 * and an ImageResizeDefinition object
 *
 */
interface OutputPathGeneratorInterface {
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
    );
}