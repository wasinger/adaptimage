<?php
namespace Wa72\AdaptImage\ResponsiveImages;

use Wa72\AdaptImage\ImageFileInfo;

/**
 * Interface for an "responsive image router"
 *
 * - finds an original (unresized) image given by its URL in the filesystem
 * - generates the URL for an resized version of a given original image
 *
 * Both tasks highly depend on the routing and controller architecture of your application,
 * so it's up to you to implement those functions and there is no default implementation.
 *
 * An object implementing this interface is needed by ResponsiveImageHelper.
 *
 * @package Wa72\AdaptImage\ResponsiveImages
 */
interface ResponsiveImageRouterInterface
{
    /**
     * Get an ImageFileInfo object of an original (not resized) image by its URL
     *
     * Implementation hint:
     * - get the local filesystem path for the image given by $original_image_url
     * - convert it to an ImageFileInfo object using ImageFileInfo::createFromFile($pathname)
     *
     * @param $original_image_url
     * @return ImageFileInfo
     */
    public function getOriginalImageFileInfo($original_image_url);

    /**
     * Generate the URL for the resized version of an original image
     *
     * Implementation hint:
     *
     * - make a controller action in your application that resizes an image, expecting 2 parameters:
     * The image (given as URL relative to your site's root) and the desired width of the resized image
     *
     * - Let your routing component compute the URL to this action with the given parameters
     *
     * @param string $original_image_url The URL pointing to the original image (usually relative to your site's root, e.g. 'images/img1.jpg')
     * @param int $image_width The width of the resized image
     * @return string The URL pointing to the resized image
     */
    public function generateUrl($original_image_url, $image_width);
}