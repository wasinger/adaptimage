<?php
namespace Wa72\AdaptImage\ImagineFilter;


use Imagine\Filter\FilterInterface;
use Imagine\Image\BoxInterface;

/**
 * Interface for a filter that resizes an image and can be asked to calculate the resulting image size
 * before actually resizing the image
 *
 * @package Wa72\AdaptImage
 */
interface ResizingFilterInterface extends FilterInterface {

    /**
     * Return the calculated new size for an image with the given original size
     * that it will have after applying this filter
     * without actually resizing it
     *
     * @param BoxInterface $size Original image size
     * @return BoxInterface New size after applying this filter
     */
    public function calculateSize(BoxInterface $size);
}