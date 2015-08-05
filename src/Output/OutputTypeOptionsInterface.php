<?php
namespace Wa72\AdaptImage\Output;


use Wa72\AdaptImage\ImagineFilter\FilterChain;

interface OutputTypeOptionsInterface
{
    /**
     * Get the image type as one of the PHP IMAGETYPE_XX constants
     * @return int
     */
    public function getType();

    /**
     * Get the file extension
     *
     * @param bool $include_dot
     * @return string
     */
    public function getExtension($include_dot = false);

    /**
     * Return a FilterChain with Filters for postprocessing the image
     *
     * @return FilterChain|null
     */
    public function getFilters();

    /**
     * Get the options for Imagine's "save()" function
     *
     * @return array
     */
    public function getSaveOptions();
}