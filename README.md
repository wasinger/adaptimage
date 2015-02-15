AdaptImage
==========

A small PHP library that lets you easily resize images to pre-defined sizes (useful for adaptive images), 
generate thumbnails, and cache them.

Built on top of the Imagine library, it is designed to be framework agnostic, customizable, and extendable.

Installation
------------

```
composer require "wa72/adaptimage": "dev-master"
```

Usage
-----

First, define the allowed image sizes in the application, matching your CSS media query breakpoints. This is done using 
`ImageResizeDefinition` objects that besides the desired image width and height may contain additional transformation
filters, such as for sharpening or adding watermarks.

```php
use Wa72\AdaptImage\ImageResizeDefinition;

$sizes = array(
    new ImageResizeDefinition(1600, 1200),
    new ImageResizeDefinition(1280, 1024),
    new ImageResizeDefinition(768, 576),
    new ImageResizeDefinition(1024, 768)
);
```

Next, we need an object implementing `OutputPathNamerInterface` that is able to compute the path and filename where a
resized image should be stored (depending on the input file and the applied transformations). `OutputPathNamerBasedir` 
is a class that generates output filenames that are all placed in per-transformation subdirectories within a common 
base directory.

```php
use Wa72\AdaptImage\OutputPathNamerBasedir;

$output_path_namer = new OutputPathNamerBasedir(__DIR__  . '/cache');
```

Next, we are ready to define an `AdaptiveImageResizer` and a `ThumbnailGenerator` that will do the work for us:

```php
use Wa72\AdaptImage\AdaptiveImageResizer;
use Wa72\AdaptImage\ThumbnailGenerator;
use Imagine\Imagick\Imagine; // or some other Imagine version

$imagine = new Imagine();

$thumbnail_generator = new ThumbnailGenerator($imagine, $output_path_namer, 150, 150, 'inset');
$resizer = new AdaptiveImageResizer($imagine, $output_path_namer, $sizes);
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





