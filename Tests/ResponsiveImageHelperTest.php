<?php
namespace Wa72\AdaptImage\Tests;


use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageClass;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageHelper;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageRouterInterface;

class ResponsiveImageHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $router;

    public function setUp()
    {
        $this->router = new MockResponsiveImageRouter();
    }

    public function testResponsiveImageHelper()
    {
        $helper = new ResponsiveImageHelper($this->router);
        $helper->addClass(new ResponsiveImageClass('first', [500, 1000, 2000], '100vw'));

        $ri = $helper->getResponsiveImage('image1.jpg', 'first');

        $srcset_expected = '/imagemock/500/image1.jpg 500w, /imagemock/1000/image1.jpg 1000w, /imagemock/2000/image1.jpg 1900w';
        $this->assertEquals($srcset_expected, $ri->getSrcsetAttributeValue());
        $this->assertEquals('100vw', $ri->getSizesAttributeValue());
    }

}

class MockResponsiveImageRouter implements ResponsiveImageRouterInterface
{
    public function getOriginalImageFileInfo($original_image_url)
    {
        return new ImageFileInfo($original_image_url, 1900, 1200, IMAGETYPE_JPEG);
    }

    public function generateUrl($original_image_url, $image_class, $image_width)
    {
        return '/imagemock/' . $image_width . '/' . $original_image_url;
    }
}
