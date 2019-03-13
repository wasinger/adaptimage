<?php
/**
 * Created by PhpStorm.
 * User: stu0014
 * Date: 13.03.2019
 * Time: 13:52
 */

namespace Wa72\AdaptImage\Tests;

use Wa72\AdaptImage\ImagineFilter\FixOrientation;
use Imagine\Gd\Imagine;
use PHPUnit\Framework\TestCase;
use Lupka\PHPUnitCompareImages\CompareImagesTrait;

class FixOrientationTest extends TestCase
{
    use CompareImagesTrait;

    protected $imagine;
    protected $tmpdir;

    public function setUp(): void
    {
        $this->tmpdir = sys_get_temp_dir();
        $this->imagine = new Imagine();
    }

    public function testFixOrientation()
    {
        $imagedir =  __DIR__ . '/data/exif-orientation-examples/';
        foreach(['Landscape', 'Portrait'] as $basename) {
            for ($i = 2; $i <= 8; $i++) {
                $image_filename = $basename . '_' . $i . '.jpg';
                $image = $imagedir . $image_filename;
                $image1 = $imagedir . 'rotated/' . $image_filename;
                $image2 = $this->tmpdir . \DIRECTORY_SEPARATOR . $image_filename;
                $orientation = $i;
                $autorotate = new FixOrientation($orientation);
                $autorotate->apply($this->imagine->open($image))->save($image2);
                $this->assertImageSimilarity($image1, $image2, $threshold = 0.01,
                    'Images not within similarity threshold: ' . $image_filename);
            }
        }
    }
}
