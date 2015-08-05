<?php
namespace Wa72\AdaptImage\Output;


use Wa72\AdaptImage\ImagineFilter\FilterChain;

class OutputTypeOptionsPng implements OutputTypeOptionsInterface
{
    private $compression_level;
    private $compression_filter;

    public function __construct($compression_level = 7, $compression_filter = 5)
    {
        $this->compression_level = $compression_level;
        $this->compression_filter = $compression_filter;
    }
    /**
     * Get the image type as one of the PHP IMAGETYPE_XX constants
     * @return int
     */
    public function getType()
    {
        return IMAGETYPE_PNG;
    }

    /**
     * Get the file extension
     *
     * @param bool $include_dot
     * @return string
     */
    public function getExtension($include_dot = false)
    {
        image_type_to_extension(IMAGETYPE_PNG, $include_dot);
    }

    /**
     * Return a FilterChain with Filters for postprocessing the image
     *
     * @return FilterChain|null
     */
    public function getFilters()
    {
        return null;
    }

    /**
     * Get the options for Imagine's "save()" function
     *
     * @return array
     */
    public function getSaveOptions()
    {
        return [
            'format' => $this->getExtension(false),
            'png_compression_level' => $this->compression_level,
            'png_compression_filter' => $this->compression_filter
        ];
    }

}