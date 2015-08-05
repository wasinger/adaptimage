<?php
namespace Wa72\AdaptImage\Output;
/**
 * This class provides a mapping between image types and OutputTypeOptions objects
 *
 */
class OutputTypeMap
{
    private $map;
    private $types;
    private $default_type;

    public function __construct()
    {
        $this->map = [
            IMAGETYPE_GIF => new OutputTypeOptionsGif(),
            IMAGETYPE_PNG => new OutputTypeOptionsPng(),
            IMAGETYPE_JPEG => new OutputTypeOptionsJpeg()
        ];
        $this->types = array_keys($this->map);
        $this->default_type = IMAGETYPE_JPEG;
    }

    /**
     * Get OutputTypeOptions for given input type
     *
     * @param int $input_type One of the IMAGETYPE_XX constants
     */
    public function getOutputTypeOptions($input_type)
    {
        if (!in_array($input_type, $this->types)) {
            $input_type = $this->default_type;
        }
        return $this->map[$input_type];
    }

    /**
     * Set an OutputTypeOptionsInterface object for a given input type
     *
     * @param int $input_type One of the IMAGETYPE_XX constants
     * @param OutputTypeOptionsInterface $options
     * @return OutputTypeMap
     */
    public function setOutputTypeOptions($input_type, OutputTypeOptionsInterface $options)
    {
        $this->map[$input_type] = $options;
        $this->types = array_keys($this->map);
        return $this;
    }

    /**
     * @param int $type One of the IMAGETYPE_XX constants
     * @return $this
     */
    public function setDefaultType($type)
    {
        $this->default_type = $type;
        return $this;
    }
}