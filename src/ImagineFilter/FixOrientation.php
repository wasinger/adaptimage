<?php
/**
 * Created by PhpStorm.
 * User: christoph
 * Date: 09.02.15
 * Time: 12:34
 */

namespace Wa72\AdaptImage\ImagineFilter;


use Imagine\Filter\FilterInterface;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;

class FixOrientation implements  FilterInterface, ResizingFilterInterface
{
    protected $orientation;

    public function __construct($orientation)
    {
        $this->orientation = $orientation;
    }

    /**
     * Applies scheduled transformation to ImageInterface instance
     * Returns processed ImageInterface instance
     *
     * @param ImageInterface $image
     *
     * @return ImageInterface
     */
    public function apply(ImageInterface $image)
    {
        switch ($this->orientation) {
            case 3:
                $image->rotate(180);
                break;
            case 6:
                $image->rotate(90);
                break;
            case 8:
                $image->rotate(-90);
                break;
            default:
                break;
        }

        return $image;
    }

    /**
     * Return the calculated new size for an image with the given original size
     * that it will have after applying this filter
     * without actually resizing it
     *
     * @param BoxInterface $size Original image size
     * @return BoxInterface New size after applying this filter
     */
    public function calculateSize(BoxInterface $size)
    {
        if ($this->orientation == 6 || $this->orientation == 8) {
            return new Box($size->getHeight(), $size->getWidth());
        }
    }

}