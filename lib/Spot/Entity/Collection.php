<?php
namespace Spot\Entity;

use Spot\Entity;

/**
 * Collection of Spot_Entity objects
 *
 * @package Spot
 */
class Collection implements \Iterator, \Countable, \ArrayAccess
{
    protected $results = [];
    protected $resultsIdentities = [];
    protected $entityName = null;

    /**
     * Constructor function
     *
     * @param array $results           Array of pre-loaded Spot_Entity objects or Iterator that will fetch them lazily
     * @param array $resultsIdentities Array of key values for given result set primary key
     */
    public function __construct(array $results = [], array $resultsIdentities = [], $entityName = null)
    {
        $this->results = $results;
        $this->resultsIdentities = $resultsIdentities;
        $this->entityName = $entityName;
    }

    /**
     * Results identities (values for all primary keys of entities in collection)
     *
     * @return array
     */
    public function resultsIdentities()
    {
        return $this->resultsIdentities;
    }

    /**
     * Entity name
     *
     * @return string Entity class name
     */
    public function entityName()
    {
        return $this->entityName;
    }

    /**
     * Returns first result in set
     *
     * @return Spot\Entity The first result in the set
     */
    public function first()
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Add a single entity to the collection
     *
     * @param object $entity to add
     */
    public function add(Entity $entity)
    {
        $this->results[] = $entity;
    }

    /**
     * Return array of raw entity objects represented in collection
     *
     * @param array Array of Entity objects in collection
     */
    public function entities()
    {
        return $this->results;
    }

    /**
    * Merge another collection into this collections set of entities
    * This will only add entitys that don't already exist in the current
    * collection
    *
    * @param Spot\Entity\Collection $collection
    * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
    */
    public function merge(Collection $collection, $onlyUnique = true)
    {
        foreach ($collection as $entity) {
            if ($onlyUnique && in_array($entity, $this->results)) {
                continue; // Skip - entity already exists in collection
            }
            $this->add($entity);
        }

        return $this;
    }

    /**
     * Return an array representation of the Collection.
     *
     * @param  mixed $keyColumn
     * @param  mixed $valueColumn
     * @return array If $keyColumn and $valueColumn are not set, or are both null
     *                           then this will return the array of entity objects
     * @return array If $keyColumn is not null, and the value column is null or undefined
     *                           then this will return an array of the values of the entities in the column defined
     * @returns array   If $keyColumn and $valueColumn are both defined and not null
     *                           then this will return an array where the key is defined by each entities value in $keyColumn
     *                           and the value will be the value of the each entity in $valueColumn
     *
     * @todo Write unit tests for this function
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        // Both empty
        if (null === $keyColumn && null === $valueColumn) {
            $return = [];
            foreach ($this->results as $row) {
                $return[] = $row->toArray();
            }

        // Key column name
        } elseif (null !== $keyColumn && null === $valueColumn) {
            $return = [];
            foreach ($this->results as $row) {
                $return[] = $row->$keyColumn;
            }

        // Both key and valud columns filled in
        } else {
            $return = [];
            foreach ($this->results as $row) {
                $return[$row->$keyColumn] = $row->$valueColumn;
            }
        }

        return $return;
    }

    /**
    * Run a function on the set of entities
    *
    * @param string|array $function A callback of the function to run
    */
    public function run($callback)
    {
         return call_user_func_array($callback, [$this->results]);
    }

    /**
     * Runs a function on every object in the query, returning the resulting array
     *
     * @param function The function to run
     * @return mixed An array containing the result of running the passed function
     *               on each member of the collect
     */
    public function map($func)
    {
        $ret = [];
        foreach ($this as $obj) {
            $ret[] = $func($obj);
        }

        return $ret;
    }

    /**
     * Runs a function on every object in the query, returning an array containing every
     *  object for which the function returns true.
     *
     * @param function The function to run
     * @return mixed An array of Entity objects
     */
    public function filter($func)
    {
        $ret = new static();
        foreach ($this as $obj) {
            if ($func($obj)) {
                $ret->add($obj);
            }
        }

        return $ret;
    }

    /**
    * Provides a string representation of the class
    * Brackets contain the number of elements contained
    * in the collection
    *
    */
    public function __toString()
    {
        return __CLASS__ . "[".$this->count()."]";
    }

    // SPL - Countable functions
    // ----------------------------------------------
    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->results);
    }

    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return current($this->results);
    }

    public function key()
    {
        return key($this->results);
    }

    public function next()
    {
        next($this->results);
    }

    public function rewind()
    {
        reset($this->results);
    }

    public function valid()
    {
        return (current($this->results) !== FALSE);
    }

    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        return isset($this->results[$key]);
    }

    public function offsetGet($key)
    {
        return $this->results[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            return $this->results[] = $value;
        } else {
            return $this->results[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->results, $key, 1);
        } else {
            unset($this->results[$key]);
        }
    }
}
