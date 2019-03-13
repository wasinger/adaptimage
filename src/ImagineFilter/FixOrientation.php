<?php
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

        //        1        2       3      4         5            6           7          8
        //
        //        888888  888888      88  88      8888888888  88                  88  8888888888
        //        88          88      88  88      88  88      88  88          88  88      88  88
        //        8888      8888    8888  8888    88          8888888888  8888888888          88
        //        88          88      88  88
        //        88          88  888888  888888


        switch ($this->orientation) {
            case 2:
                $image->flipHorizontally();
                break;
            case 3:
                $image->rotate(180);
                break;
            case 4:
                $image->flipVertically();
                break;
            case 5:
                $image->rotate(90)->flipHorizontally();
                break;
            case 6:
                $image->rotate(90);
                break;
            case 7:
                $image->rotate(-90)->flipHorizontally();
                break;
            case 8:
                $image->rotate(-90);
                break;
            default:
                break;
        }

        // after rotating the image, we need to reset orientation in Exif data to 1
        if ($image instanceof \Imagine\Imagick\Image) {
            $image->getImagick()->setImageOrientation(1);
        } else {
            // TODO: how can this be done in GD and Gmagick?
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
        if ($this->orientation >= 5) {
            // in orientation values >= 5 width and height are swapped
            return new Box($size->getHeight(), $size->getWidth());
        } else {
            return $size;
        }
    }

}