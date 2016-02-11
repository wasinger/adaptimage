AdaptImage
==========

[![Build Status](https://travis-ci.org/wasinger/adaptimage.svg?branch=master)](https://travis-ci.org/wasinger/adaptimage)
[![Latest Version](http://img.shields.io/packagist/v/wa72/adaptimage.svg)](https://packagist.org/packages/wa72/adaptimage)

A small PHP library that lets you easily resize images to pre-defined sizes (useful for adaptive images), 
generate thumbnails, and cache them.

Built on top of the [Imagine](https://github.com/avalanche123/Imagine) library, it is designed to be framework agnostic,
object orientated, customizable, and extendable.

__Features__:

-   Allows to define multiple allowed image sizes. Each defined image size can be given additional
    [Imagine filters](http://imagine.readthedocs.org/en/latest/_static/API/Imagine/Filter/FilterInterface.html), 
    e.g. for sharpening.
-   The resized images are written to cache files whose names are computed from the input image and the applied
    transformation. You can easily write your own naming roules by implementing
    [`OutputPathGeneratorInterface`](src/Output/OutputPathGeneratorInterface.php). The next time a resized image is required the
    cached file is returned. The cached file will be regenerated when the original file changes.
-   __New__: Helper classes for responsive images according to the HTML5 spec with `srcset` and `sizes` attritubes that generate the resized image files and the value for the `srcset` attribute.   
-   Adaptive images: acquire an image for an arbitrary screen width and get a resized image with the nearest defined size.
    You can select whether you want the next smaller images (that completely fits into the given width) or the next
    bigger image (that can be downscaled by the browser to fill the screen).
-   Thumbnails: supports "inset" and "outbound" (crop) modes, supports custom crops for single images.
-   Ability to simulate resize operations, i.e. calculating the resulting width and height of an image without actually
    transforming it. Useful for generating lots of thumbnail `<img>` tags in an html page with width and height
    attributes.
-   When generating resized images lockfiles are used to prevent race conditions.  

Installation
------------

```
composer require "wa72/adaptimage"
```

Usage
-----

First, define the allowed image sizes in the application, matching your CSS media query breakpoints. This is done using 
`ImageResizeDefinition` objects that besides the desired image width and height may contain additional transformation
filters, such as for sharpening or adding watermarks.

```php
use Wa72\AdaptImage\ImageResizeDefinition;
use Wa72\AdaptImage\ImagineFilter\Sharpen;

$sizes = array(
    ImageResizeDefinition::create(1600, 1200),
    ImageResizeDefinition::create(1280, 1024),
    ImageResizeDefinition::create(768, 576),
    ImageResizeDefinition::create(1024, 768)->addFilter(new Sharpen() // example with additional sharpen filter
);
```

Next, we need an object implementing [`OutputPathGeneratorInterface`](src/Output/OutputPathGeneratorInterface.php) that is able to
compute the path and filename where a resized image should be stored (depending on the input file and the applied 
transformations).
[`OutputPathGeneratorBasedir`](src/Output/OutputPathGeneratorBasedir.php) is a class that generates output filenames that are all
placed in per-transformation subdirectories within a common base directory.

```php
use Wa72\AdaptImage\Output\OutputPathGeneratorBasedir;

$cachedir = __DIR__  . '/cache';
$output_path_generator = new OutputPathGeneratorBasedir($cachedir);
```

Now we are ready to define an [`AdaptiveImageResizer`](src/AdaptiveImageResizer.php) and a 
[`ThumbnailGenerator`](src/ThumbnailGenerator.php) that will do the work for us:

```php
use Wa72\AdaptImage\AdaptiveImageResizer;
use Wa72\AdaptImage\ThumbnailGenerator;
use Imagine\Imagick\Imagine; // or some other Imagine version

$imagine = new Imagine();

$thumbnail_generator = new ThumbnailGenerator($imagine, $output_path_generator, 150, 150, 'inset');
$resizer = new AdaptiveImageResizer($imagine, $output_path_generator, $sizes);
```

Both the `ThumbnailGenerator` and the `AdaptiveImageResizer` get an `ImageFileInfo` object of the original file as
input and will return another `ImageFileInfo` object pointing to the generated resized image file. The ThumbnailGenerator
will generate a thumbnail with the dimensions defined in the constructor, while the AdaptiveImageResizer will from
the defined image sizes select the one that best matches `$client_screen_width` and will scale the image to the defined
size. Both will resize the image only if there isn't a resized version yet or if the original file is newer than the
resized one, but just return the cached file if it is already there.

```php
use Wa72\AdaptImage\ImageFileInfo;

$image = ImageFileInfo::createFromFile('/path/to/original/image');

$thumbnail = $thumbnail_generator->thumbnail(true, $image);

$client_screen_width = 1024; // typically you get this value from a cookie

$resized_image = $resizer->resize(true, $image, $client_screen_width);
```

`$thumbnail` and `$resized_image` are `ImageFileInfo` objects containing the path and name of the generated file
as well as it's image type, width, and height. Use this information for generating html `img` tags or deliver the
resized image to the user, e.g. using a `BinaryFileResponse` from Symfony:

```php
$response = new Symfony\Component\HttpFoundation\BinaryFileResponse($resized_image->getPathname());
$response->prepare($request);
return $response;
```

For more documentation, please see the source code, it should be well commented.


© 2015 Christoph Singer. Licensed under the MIT license.


