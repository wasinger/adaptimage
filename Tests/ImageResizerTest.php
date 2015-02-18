<?php
namespace Wa72\AdaptImage\Tests;

use Imagine\Gd\Imagine;
use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\ImageResizer;
use Wa72\AdaptImage\OutputPathNamerBasedir;

class ImageResizerTest extends \PHPUnit_Framework_TestCase
{
    protected $output_path_namer;
    protected $imagine;

    public function setUp()
    {
        $cachedir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ai-cache';
        $this->output_path_namer = new OutputPathNamerBasedir($cachedir);
        $this->imagine = new Imagine();
    }

    public function testResize()
    {
        $ird = new ImageResizeDefinition(300, 300);
        $resizer = new ImageResizer($this->imagine, $ird, $this->output_path_namer);

        $image = new ImageFileInfo('tmp.jpg', 600, 200, IMAGETYPE_JPEG);
        $resized_image = $resizer->resize($image, false);
        $this->assertEquals(300, $resized_image->getWidth());
        $this->assertEquals(100, $resized_image->getHeight());

        $image = new ImageFileInfo('tmp.jpg', 600, 200, IMAGETYPE_JPEG, 0, 6);
        $resized_image = $resizer->resize($image, false);
        $this->assertEquals(100, $resized_image->getWidth());
        $this->assertEquals(300, $resized_image->getHeight());

        $image = new ImageFileInfo('tmp.jpg', 200, 600, IMAGETYPE_JPEG);
        $resized_image = $resizer->resize($image, false);
        $this->assertEquals(100, $resized_image->getWidth());
        $this->assertEquals(300, $resized_image->getHeight());

        $image = new ImageFileInfo('tmp.jpg', 80, 80, IMAGETYPE_JPEG);
        $resized_image = $resizer->resize($image, false);
        $this->assertEquals(80, $resized_image->getWidth());
        $this->assertEquals(80, $resized_image->getHeight());

        // Test with upscaling
        $ird = new ImageResizeDefinition(300, 300, ImageResizeDefinition::MODE_MAX, true);
        $resizer = new ImageResizer($this->imagine, $ird, $this->output_path_namer);

        $image = new ImageFileInfo('tmp.jpg', 80, 80, IMAGETYPE_JPEG);
        $resized_image = $resizer->resize($image, false);
        $this->assertEquals(300, $resized_image->getWidth());
        $this->assertEquals(300, $resized_image->getHeight());
    }

}