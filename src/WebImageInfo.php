<?php

namespace Wa72\AdaptImage;

/**
 * This class holds information about a web image, such as URL, width and height.
 *
 */
class WebImageInfo
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var int
     */
    protected $width;
    /**
     * @var int
     */
    protected $height;
    /**
     * @var string
     */
    protected $mimetype;

    /**
     * WebImageInfo constructor.
     * @param string $url
     * @param int $width
     * @param int $height
     * @param string $mimetype
     */
    public function __construct($url, $width, $height, $mimetype)
    {
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
        $this->mimetype = $mimetype;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }
}