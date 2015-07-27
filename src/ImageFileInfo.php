<?php
namespace Wa72\AdaptImage;

/**
 * Class ImageFileInfo represents information about an image file
 *
 * @package Wa72\AdaptImage
 */
class ImageFileInfo {
    static $allowed_image_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

    protected $pathname;
    protected $filename;
    /**
     * @var int
     */
    protected $width;
    /**
     * @var int
     */
    protected $height;
    /**
     * @var string file extension (without the dot, e.g. "jpg")
     */
    protected $extension;
    /**
     * @var int One of the PHP IMAGETYPE_ constants (e.g. IMAGETYPE_JPEG)
     */
    protected $imagetype;
    /**
     * @var string The mimetype, e.g. "image/jpeg"
     */
    protected $mimetype;

    /**
     * @var \SplFileInfo
     */
    protected $fileinfo;

    protected $orientation = 0;

    /**
     * @param string $pathname
     * @param int $width
     * @param int $height
     * @param int $imagetype
     * @param int $last_modified
     * @param int $orientation
     */
    public function __construct($pathname, $width, $height, $imagetype, $last_modified = 0, $orientation = 0)
    {
        $this->pathname = $pathname;
        $this->fileinfo = new \SplFileInfo($this->pathname);
        $this->extension = strtolower($this->fileinfo->getExtension());
        $this->filename = $this->fileinfo->getFilename();
        $this->filemtime = $last_modified;
        $this->width = $width;
        $this->height = $height;
        $this->imagetype = $imagetype;
        $this->mimetype = image_type_to_mime_type($imagetype);
        $this->orientation = $orientation;
    }

    static public function createFromFile($pathname)
    {
        if (!file_exists($pathname)) {
            throw new \Exception('File ' . $pathname . ' not found.');
        }
        $ii = getimagesize($pathname);
        $width = $ii[0];
        $height = $ii[1];
        $imagetype = $ii[2];
        if (!in_array($imagetype, self::$allowed_image_types)) {
            throw new \InvalidArgumentException(sprintf('File %s is not an allowed image type', $pathname));
        }
        $last_modified = filemtime($pathname);
        $exif = @exif_read_data($pathname);
        if (isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
        } else {
            $orientation = 0;
        }
        return new static($pathname, $width, $height, $imagetype, $last_modified, $orientation);
    }

    /**
     * @return \SplFileInfo
     */
    public function getFileinfo()
    {
        return $this->fileinfo;
    }

    /**
     * @return string
     */
    public function getPathname()
    {
        return $this->pathname;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
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
     * Get the file extension (without the dot, e.g. "jpg")
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Get the image type as PHP IMAGETYPE_ constant (e.g. IMAGETYPE_JPEG)
     *
     * @return integer One of the PHP IMAGETYPE_ constants (e.g. IMAGETYPE_JPEG)
     */
    public function getImagetype()
    {
        return $this->imagetype;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * @return int
     */
    public function getFilemtime()
    {
        return $this->filemtime;
    }

    /**
     * @return int
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

}