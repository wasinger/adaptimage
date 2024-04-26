<?php
namespace Wa72\AdaptImage;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Wa72\AdaptImage\Exception\FiletypeNotSupportedException;
use Wa72\AdaptImage\Exception\ImageFileNotFoundException;
use Wa72\AdaptImage\Exception\ImageResizingFailedException;
use Wa72\AdaptImage\ImagineFilter\FilterChain;
use Wa72\AdaptImage\ImagineFilter\FixOrientation;
use Wa72\AdaptImage\Output\OutputPathGeneratorInterface;
use Wa72\AdaptImage\Output\OutputTypeOptionsInterface;

/**
 * Class ImageResizer applies a Transformation to an image and caches the resulting image.
 *
 */
class ImageResizer {
    /** @var OutputPathGeneratorInterface  */
    protected $output_path_generator;

    /**
     * @var ImagineInterface
     */
    protected $imagine;

    /**
     * @param ImagineInterface $imagine
     * @param OutputPathGeneratorInterface $output_path_generator
     */
    public function __construct(
        ImagineInterface $imagine,
        OutputPathGeneratorInterface $output_path_generator
    )
    {
        $this->imagine = $imagine;
        $this->output_path_generator = $output_path_generator;
    }

    /**
     * Apply transformation to image. Return an ImageFileInfo object with information about the resulting file.
     * If a cached version of this image/transformation combination already exists, the cached version will be returned.
     *
     * @param ImageResizeDefinition $image_resize_definition
     * @param ImageFileInfo $image
     * @param bool $really_do_it If false, the image will not be really processed, but instead the resulting size is calculated
     * @param FilterChain|null $pre_transformation Custom Transformation for this image
     *                                                  that will be applied before the resizing transformation
     *                                                  Used for image rotation and custom thumbnail crops
     * @return ImageFileInfo|static
     * @throws FiletypeNotSupportedException If the original image type is not supported
     * @throws ImageFileNotFoundException If the original image file does not exist or is not readable
     * @throws ImageResizingFailedException If the image could not be resized
     */
    public function resize(ImageResizeDefinition $image_resize_definition, ImageFileInfo $image, $really_do_it = false, $pre_transformation = null)
    {
        if (!$image->isSupported()) {
            throw new FiletypeNotSupportedException($image->getPathname());
        }
        if ($image->getOrientation() != 1) {
            if ($pre_transformation == null) {
                $pre_transformation = new FilterChain($this->imagine);
            }
            $pre_transformation->add(new FixOrientation($image->getOrientation()));
        }

        $outputTypeOptions = $image_resize_definition->getOutputTypeMap()->getOutputTypeOptions($image->getImagetype());

        $cachepath = $this->output_path_generator->getOutputPathname(
            $image,
            $image_resize_definition,
            $outputTypeOptions->getExtension(false),
            $pre_transformation
        );

        // if cached file already exists just return it
        if (file_exists($cachepath) && filemtime($cachepath) > $image->getFilemtime()) {
            return ImageFileInfo::createFromFile($cachepath);
        }

        if ($pre_transformation instanceof FilterChain) {
            $transformation = new FilterChain($this->imagine);
            $transformation->append($pre_transformation);
            $transformation->append($image_resize_definition->getResizeTransformation());
        } else {
            $transformation = $image_resize_definition->getResizeTransformation();
        }

        $post_transformation = $image_resize_definition->getPostTransformation();

        if (!$really_do_it) {
            // calculate size after transformation
            $size = $transformation->calculateSize(new Box($image->getWidth(), $image->getHeight()));
            return new ImageFileInfo($cachepath, $size->getWidth(), $size->getHeight(), $outputTypeOptions->getType(), 0);
        }

        // check whether original file exists
        if (!$image->fileExists()) {
            throw new ImageFileNotFoundException($image->getPathname());
        }

        $count = 0;
        while ($this->_doTransform($image, $transformation, $outputTypeOptions, $post_transformation, $cachepath) === false) {
            if ($count > 4) {
                throw new ImageResizingFailedException('Could not generate Thumbnail');
            }
            sleep(2);
            $count++;
        }
        return ImageFileInfo::createFromFile($cachepath);
    }

    private function _doTransform(ImageFileInfo $image, FilterChain $transformation, OutputTypeOptionsInterface $outputTypeOptions, FilterChain $post_transformation, $cache_path)
    {
        $lockfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($cache_path) . '.lock';
        $lockfp = fopen($lockfile, 'w');
        if (flock($lockfp, LOCK_EX | LOCK_NB)) {
            if (!file_exists($cache_path) || filemtime($cache_path) < $image->getFilemtime()) {
                $cachedir = dirname($cache_path);
                if (!file_exists($cachedir)) {
                    mkdir($cachedir, 0777, true);
                }
                $oldsize = new Box($image->getWidth(), $image->getHeight());
                $newsize = $transformation->calculateSize($oldsize);
                if ($oldsize == $newsize && $image->getImagetype() == $outputTypeOptions->getType()) {
                    // image needs neither resizing nor type conversion: skip transformation chain
                    if (count($post_transformation)) {
                        // if there are post_transformation filters, we need to apply them
                        try {
                            $ii = $post_transformation->apply($this->imagine->open($image->getPathname()));
                            if ($outputTypeOptions->getFilters() instanceof FilterChain) {
                                $outputTypeOptions->getFilters()->apply($ii);
                            }
                            $ii->save($cache_path, $outputTypeOptions->getSaveOptions());
                        } catch (\RuntimeException $e) {
                            throw new ImageResizingFailedException(
                                sprintf('Image %s could not be resized.', $image->getPathname()),
                                0,
                                $e
                            );
                        }
                    } else {
                        // no transformation needed at all: just copy the image
                        copy($image->getPathname(), $cache_path);
                    }
                } else {
                    try {
                        $ii = $transformation->apply($this->imagine->open($image->getPathname()));
                        if (count($post_transformation)) {
                            $post_transformation->apply($ii);
                        }
                        if ($outputTypeOptions->getFilters() instanceof FilterChain) {
                            $outputTypeOptions->getFilters()->apply($ii);
                        }
                        $ii->save($cache_path, $outputTypeOptions->getSaveOptions());
                    } catch (\RuntimeException $e) {
                        throw new ImageResizingFailedException(
                            sprintf('Image %s could not be resized.', $image->getPathname()),
                            0,
                            $e
                        );
                    }
                }
            }
            unlink($lockfile);
            fclose($lockfp);
            return true;
        } else {
            fclose($lockfp);
            return false;
        }
    }
}