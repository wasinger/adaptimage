<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Exception\NotSupportedException;
use Imagine\Image\ImageInterface;
use Imagine\Filter\FilterInterface;

class UnsharpMask implements FilterInterface
{
    const levels = 255;

    private $radius;
    private $amount;
    private $sigma;
    private $threshold;

    /**
     * @param int|float $radius Radius parameter as used in GIMP or Photoshop
     * @param int|float $amount Percentage as decimal number (100% = 1), range from 0 to 10.
     *                      Values higher than 10 are interpreted as percentage integer
     *                      and will be divided by 100 (170 becomes 1.7)
     * @param int|float $threshold an Integer between 0 and 255,
     *                             or a float between 0 and 1 indicating the percentage of 255
     */
    public function __construct($radius, $amount = 1, $threshold = 0)
    {
        $this->radius = $radius;

        // $amount: normalize to decimal numbers
        if ($amount > 10) {
            $amount = $amount / 100;
        }
        $this->amount = $amount;

        // calculate a useful ImageMagick "sigma" parameter from a "radius" parameter used in GIMP or PS
        if ($radius < 1) {
            $this->sigma = $radius;
        } else {
            $this->sigma = sqrt($radius);
        }

        // $threshold: normalize level integers to decimal percentage values used in ImageMagick
        if ($threshold >= 1 && $threshold <= self::levels) {
            $threshold = $threshold / self::levels;
        }
        $this->threshold = $threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        if ($image instanceof \Imagine\Imagick\Image) {
            $image->getImagick()->unsharpMaskImage(0, $this->sigma, $this->amount, $this->threshold);
        } else {
            throw new NotSupportedException('Unsharp mask is not yet supported in this adapter');
        }
        return $image;
    }
}