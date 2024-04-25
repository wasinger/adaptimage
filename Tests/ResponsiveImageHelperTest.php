<?php
namespace Wa72\AdaptImage\Tests;


use PHPUnit\Framework\TestCase;
use Wa72\AdaptImage\Exception\FiletypeNotSupportedException;
use Wa72\AdaptImage\ImageFileInfo;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageClass;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageHelper;
use Wa72\AdaptImage\ResponsiveImages\ResponsiveImageRouterInterface;

class ResponsiveImageHelperTest extends TestCase
{
    protected $router;

    public function setUp(): void
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

        $dom = new \DOMDocument();
        $img = $dom->createElement('img');
        $img->setAttribute('src', 'image2.jpg');
        $helper->makeImgElementResponsive($img, 'first', true, true);
        $srcset_expected = '/imagemock/500/image2.jpg 500w, /imagemock/1000/image2.jpg 1000w, /imagemock/2000/image2.jpg 1900w';
        $this->assertEquals($srcset_expected, $img->getAttribute('srcset'));
        $this->assertEquals('100vw', $img->getAttribute('sizes'));
        $this->assertEquals('/imagemock/500/image2.jpg', $img->getAttribute('src'));
        $this->assertEquals('500', $img->getAttribute('width'));
        $this->assertEquals('316', $img->getAttribute('height'));

        // don't touch svg images
        $img = $dom->createElement('img');
        $img->setAttribute('src', 'image3.svg');
        $helper->makeImgElementResponsive($img, 'first', true, true);
        $this->assertFalse($img->hasAttribute('srcset'));
        $this->assertFalse($img->hasAttribute('sizes'));
        $this->assertEquals('image3.svg', $img->getAttribute('src'));

    }

}

class MockResponsiveImageRouter implements ResponsiveImageRouterInterface
{
    public function getOriginalImageFileInfo($original_image_url)
    {
        $ext = pathinfo($original_image_url, PATHINFO_EXTENSION);
        if ($ext != 'jpg') throw new FiletypeNotSupportedException($original_image_url);
        return new ImageFileInfo($original_image_url, 1900, 1200, IMAGETYPE_JPEG);
    }

    public function generateUrl($original_image_url, $image_class, $image_width)
    {
        return '/imagemock/' . $image_width . '/' . $original_image_url;
    }
}
