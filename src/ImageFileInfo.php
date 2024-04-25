<?php
namespace Wa72\AdaptImage;

use Wa72\AdaptImage\Exception\FiletypeNotSupportedException;
use Wa72\AdaptImage\Exception\ImageFileNotFoundException;
use Wa72\AdaptImage\Exception\ImageTypeNotSupportedException;

/**
 * Class ImageFileInfo represents information about an image file
 *
 */
class ImageFileInfo {
    protected $pathname;
    protected $filename;
    protected $filemtime;
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
     * ImageFileInfo constructor.
     * There is no check whether $pathname really exists, so it is possible to create ImageFileInfo objects
     * for non-existing files. This is used to carry information about resized images and thumbnails before
     * they are actually created. Use the fileExists() method to check whether the file exists,
     * and use the static creator function ImageFileInfo::createFromFile($pathname) to create
     * ImageFileInfo objects from real image files.
     *
     * @see ImageFileInfo::createFromFile()
     *
     * @param string $pathname
     * @param int $width
     * @param int $height
     * @param int $imagetype One of the PHP IMAGETYPE_ constants (e.g. IMAGETYPE_JPEG)
     * @param int $last_modified Unix timestamp as returned by filemtime()
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

    /**
     * Create an ImageFileInfo from an image file
     *
     * @param string $pathname The pathname of an image file.
     * @return ImageFileInfo
     * @throws ImageFileNotFoundException If the image does not exist
     * @throws FiletypeNotSupportedException If the image is not supported by PHP getimagesize() function
     */
    static public function createFromFile($pathname): ImageFileInfo
    {
        if (!file_exists($pathname)) {
            throw new ImageFileNotFoundException($pathname);
        }
        $ii = getimagesize($pathname);
        if ($ii === FALSE) {
            throw new FiletypeNotSupportedException($pathname);
        }
        $width = $ii[0];
        $height = $ii[1];
        $imagetype = $ii[2];
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
     * @return bool
     */
    public function fileExists()
    {
        return file_exists($this->pathname);
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