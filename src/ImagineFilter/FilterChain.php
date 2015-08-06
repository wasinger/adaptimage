<?php
namespace Wa72\AdaptImage\ImagineFilter;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Filter\ImagineAware;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\BoxInterface;
use Imagine\Filter\FilterInterface;

/**
 * Class FilterChain is a chain of Imagine Filters that can be applied to an image in a specified order.
 *
 * It is basically the same thing as an Imagine "Transformation" but with additional functions for appending and prepending
 * other filter chains and for calculating the resulting size of an image when resizing filters are applied.
 *
 * I would have just been extending the Imagine\Filter\Transformation class but it is declared as final so I needed to
 * copy'n'paste the code from there to here.
 *
 * On the other hand, this class misses the functions from ManipulatorInterface that are not needed here.
 *
 * @author Christoph Singer
 *
 * many functions directly taken from Imagine\Filter\Transformation class
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * @package Wa72\AdaptImage
 */
class FilterChain implements FilterInterface, ResizingFilterInterface
{
    /**
     * @var array
     */
    private $filters = array();

    /**
     * @var array
     */
    private $sorted;

    /**
     * An ImagineInterface instance.
     *
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * Class constructor.
     *
     * @param ImagineInterface $imagine An ImagineInterface instance
     */
    public function __construct(ImagineInterface $imagine = null)
    {
        $this->imagine = $imagine;
    }

    /**
     * Applies a given FilterInterface onto given ImageInterface and returns
     * modified ImageInterface
     *
     * @param ImageInterface  $image
     * @param FilterInterface $filter
     *
     * @return ImageInterface
     * @throws InvalidArgumentException
     */
    public function applyFilter(ImageInterface $image, FilterInterface $filter)
    {
        if ($filter instanceof ImagineAware) {
            if ($this->imagine === null) {
                throw new InvalidArgumentException(sprintf('In order to use %s pass an Imagine\Image\ImagineInterface instance to Transformation constructor', get_class($filter)));
            }
            $filter->setImagine($this->imagine);
        }

        return $filter->apply($image);
    }

    /**
     * Returns a list of filters sorted by their priority. Filters with same priority will be returned in the order they were added.
     *
     * @return array
     */
    public function getFilters()
    {
        if (null === $this->sorted) {
            if (!count($this->filters)) {
                $this->sorted = array();
            } else {
                ksort($this->filters);
                $this->sorted = call_user_func_array('array_merge', $this->filters);
            }
        }
        return $this->sorted;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        return array_reduce(
            $this->getFilters(),
            array($this, 'applyFilter'),
            $image
        );
    }

    /**
     * Registers a given FilterInterface in an internal array of filters for
     * later application to an instance of ImageInterface
     *
     * @param  FilterInterface $filter
     * @param  int             $priority
     * @return FilterChain
     */
    public function add(FilterInterface $filter, $priority = 0)
    {
        $this->filters[$priority][] = $filter;
        $this->sorted = null;

        return $this;
    }

    /**
     * Prepend filters from another filter chain
     *
     * @param FilterChain $chain
     * @return FilterChain
     */
    public function prepend(FilterChain $chain)
    {
        $priorities = array_keys($this->filters);
        if (count($priorities)) {
            $priority = min(array_keys($this->filters)) - 1;
        } else {
            $priority = 0;
        }
        $filters = $chain->getFilters();
        foreach ($filters as $filter) {
            $this->add($filter, $priority);
        }
        return $this;
    }

    /**
     * Append filters from another filter chain
     *
     * @param FilterChain $chain
     * @return FilterChain
     */
    public function append(FilterChain $chain)
    {
        $priorities = array_keys($this->filters);
        if (count($priorities)) {
            $priority = max(array_keys($this->filters)) + 1;
        } else {
            $priority = 0;
        }
        $filters = $chain->getFilters();
        foreach ($filters as $filter) {
            $this->add($filter, $priority);
        }
        return $this;
    }

    /**
     * Return the calculated new size for an image with the given original size
     * that it will have after applying this filter
     * without actually resizing it
     *
     * @param BoxInterface $size Original image size
     * @return BoxInterface New size after applying this filter
     */
    public function calculateSize(BoxInterface $size)
    {
        $filters = $this->getFilters();
        foreach ($filters as $filter) {
            if ($filter instanceof ResizingFilterInterface) {
                $size = $filter->calculateSize($size);
            }
        }
        return $size;
    }


}