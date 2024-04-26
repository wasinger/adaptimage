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
     *
     * There is no check whether $pathname really exists and no data is read from the file,
     * so it is possible to create ImageFileInfo objects for non-existing files.
     * This is used to carry information about resized images and thumbnails before
     * they are actually created. Use the fileExists() method to check whether the file exists,
     * and use the static creator function ImageFileInfo::createFromFile($pathname) to create
     * ImageFileInfo objects from real image files.
     *
     * When creating ImageFileInfo objects for files that are not supported by PHP
     * (no IMAGETYPE_* constant exists) pass null for $imagetype.
     *
     * If no $mimetype is passed the mimetype will be derived from $imagetype.
     * When $imagetype is null the mimetype will be guessed from the file extension.
     *
     * @see ImageFileInfo::createFromFile()
     *
     * @param string $pathname
     * @param int $width
     * @param int $height
     * @param int $imagetype One of the PHP IMAGETYPE_ constants (e.g. IMAGETYPE_JPEG)
     * @param int $last_modified Unix timestamp as returned by filemtime()
     * @param int $orientation
     * @param int|null $mimetype
     */
    public function __construct($pathname, $width, $height, $imagetype = null, $last_modified = 0, $orientation = 0, $mimetype = null)
    {
        $this->pathname = $pathname;
        $this->fileinfo = new \SplFileInfo($this->pathname);
        $this->extension = strtolower($this->fileinfo->getExtension());
        $this->filename = $this->fileinfo->getFilename();
        $this->filemtime = $last_modified;
        $this->width = $width;
        $this->height = $height;
        $this->imagetype = $imagetype;
        $this->mimetype = $mimetype;
        if (!$mimetype) {
            if ($imagetype) {
                $this->mimetype = image_type_to_mime_type($imagetype);
            } else {
                $this->mimetype = self::guessMimeTypeFromExtension($this->extension);
            }
        }
        $this->orientation = $orientation;
    }

    /**
     * Create an ImageFileInfo from an existing file
     *
     * If the file is not an image file supported by PHP nevertheless an ImageFileInfo object will
     * be returned with $imagetype set to null. Use the isSupported()-Method to check for this case.
     *
     * @param string $pathname The pathname of an image file.
     * @return ImageFileInfo
     * @throws ImageFileNotFoundException If the image does not exist
     */
    static public function createFromFile(string $pathname): ImageFileInfo
    {
        if (!file_exists($pathname)) {
            throw new ImageFileNotFoundException($pathname);
        }
        $ii = getimagesize($pathname);
        if ($ii !== FALSE) {
            // image format supported by PHP
            $width = $ii[0];
            $height = $ii[1];
            $imagetype = $ii[2];
            $mimetype = image_type_to_mime_type($imagetype);
            $exif = @exif_read_data($pathname);
            $orientation = $exif['Orientation'] ?? 0;
        } else {
            // image format not supported by PHP
            $width = 0;
            $height = 0;
            $imagetype = null;
            $mimetype = mime_content_type($pathname);
            $orientation = 0;
        }

        $last_modified = filemtime($pathname);

        return new static($pathname, $width, $height, $imagetype, $last_modified, $orientation, $mimetype);
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

    public function isSupported(): bool
    {
        return $this->imagetype !== null;
    }

    /**
     * Guess mime type from file extension
     *
     * @param string $ext
     * @return string
     */
    static function guessMimeTypeFromExtension(string $ext): string
    {
        $a = [
            'aac' => 'audio/aac',
            'abw' => 'application/x-abiword',
            'arc' => 'application/x-freearc',
            'avif' => 'image/avif',
            'avi' => 'video/x-msvideo',
            'azw' => 'application/vnd.amazon.ebook',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2',
            'cda' => 'application/x-cdf',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'eot' => 'application/vnd.ms-fontobject',
            'epub' => 'application/epub+zip',
            'gz' => 'application/gzip',
            'gif' => 'image/gif',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ico' => 'image/vnd.microsoft.icon',
            'ics' => 'text/calendar',
            'jar' => 'application/java-archive',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'jsonld' => 'application/ld+json',
            'mid' => 'audio/midi audio/x-midi',
            'midi' => 'audio/midi audio/x-midi',
            'mjs' => 'text/javascript',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpeg' => 'video/mpeg',
            'mpkg' => 'application/vnd.apple.installer+xml',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'oga' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'opus' => 'audio/opus',
            'otf' => 'font/otf',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'php' => 'application/x-httpd-php',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar' => 'application/vnd.rar',
            'rtf' => 'application/rtf',
            'sh' => 'application/x-sh',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tar' => 'application/x-tar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'ts' => 'video/mp2t',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain',
            'vsd' => 'application/vnd.visio',
            'wav' => 'audio/wav',
            'weba' => 'audio/webm',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'text/xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'zip' => 'application/zip',
            '3gp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            '7z' => 'application/x-7z-compressed'
        ];
        return $a[$ext] ?? '';
    }
}