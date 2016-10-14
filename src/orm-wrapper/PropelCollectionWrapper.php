<?php

namespace Athens\Propel\ORMWrapper;

use Propel\Runtime\Collection\ObjectCollection;

use Propel\Runtime\Collection\CollectionIterator;

use Athens\Core\ORMWrapper\CollectionWrapperInterface;
use Athens\Core\ORMWrapper\AbstractCollectionWrapper;

/**
 * Class PropelCollectionWrapper
 *
 * @package Athens\Propel\ORMWrapper
 */
class PropelCollectionWrapper extends AbstractCollectionWrapper
{
    /** @var ObjectCollection */
    protected $collection;
    
    /** @var CollectionIterator */
    protected $iterator;

    /**
     * PropelCollectionWrapper constructor.
     * @param ObjectCollection $collection
     */
    public function __construct(ObjectCollection $collection)
    {
        $this->collection = $collection;
        $this->iterator = new CollectionIterator($collection);
    }

    /**
     * Move the iterator index to the next item.
     * @return void
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * Find whether the current iterator index is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * Move the iterator index back to its initial position.
     *
     * @return void
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * Return the current array key.
     *
     * @return mixed
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * Get the item at the current iterator index.
     *
     * @return PropelObjectWrapper
     */
    public function current()
    {
        return PropelObjectWrapper::fromObject($this->iterator->current());
    }

    /**
     * Set the element at the given offset to the given value.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->collection->offsetSet($offset, $value);
    }

    /**
     * Retrieve the element at the given array offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->collection->offsetGet($offset);
    }

    /**
     * Unset the element at the given array offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->collection->offsetUnset($offset);
    }

    /**
     * Find whether the array has an element set at the given offset.
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->collection->offsetExists($offset);
    }

    /**
     * Count the number of elements.
     *
     * @return integer
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Save all of the elements in the collection to the database.
     * @return void
     */
    public function save()
    {
        $this->collection->save();
    }

    /**
     * Delete all of the elements in the collection from the database.
     * @return void
     */
    public function delete()
    {
        $this->collection->delete();
    }
}
