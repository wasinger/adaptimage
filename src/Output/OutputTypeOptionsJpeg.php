<?php
namespace Wa72\AdaptImage\Output;


use Wa72\AdaptImage\ImagineFilter\FilterChain;

class OutputTypeOptionsJpeg implements OutputTypeOptionsInterface
{
    private $quality;
    private $progressive;

    /**
     * @param int $quality
     * @param bool $progressive
     */
    public function __construct($quality = 85, $progressive = false) {
        $this->quality = $quality;
        $this->progressive = $progressive;
    }

    /**
     * Get the image type as one of the PHP IMAGETYPE_XX constants
     * @return int
     */
    public function getType()
    {
        return IMAGETYPE_JPEG;
    }

    /**
     * Get the file extension
     *
     * @param bool $include_dot
     * @return string
     */
    public function getExtension($include_dot = false)
    {
        image_type_to_extension(IMAGETYPE_JPEG, $include_dot);
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
            'jpeg_quality' => $this->quality
        ];
    }
}