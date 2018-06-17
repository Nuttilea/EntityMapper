<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/16/18
 * Time: 3:51 PM
 */

namespace Nuttilea\EntityMapper;


use Nuttilea\EntityMapper\Exception\Exception;

class Result implements \Iterator {

    const DETACHED_ROW_ID = -1;

    /** @var Connection */
    protected $connection;

    /** @var bool */
    protected $attached = true;

    protected $referencedRows = [];

    /** @var ReflectionEntity */
    protected $reflectionEntity;

    protected $collection = [];

    protected $keys = [];

    /** @var Mapper */
    protected $mapper;


    private function __construct($collection = null, ReflectionEntity $reflectionEntity = null, Connection $connection = null, Mapper $mapper = null) {
        $this->reflectionEntity = $reflectionEntity;
        $this->connection = $connection;
        $this->mapper = $mapper;
        $this->collection = $collection !== null ? $collection : [self::DETACHED_ROW_ID => []];
        $this->attached = isset($collection) && $reflectionEntity && $collection && $mapper;
    }

    public static function createAttachedInstance($collection, ReflectionEntity $reflectionEntity, Connection $connection, Mapper $mapper){
        $primaryKey = implode('#',$reflectionEntity->getPrimary());
        $dataCollection = [];
        foreach ($collection as $row) {
            $row = (array) $row;
            if(isset($row[$primaryKey])) {
                $dataCollection[$row[$primaryKey]] = $row;
            } else {
                $dataCollection[] = $row;
            }
        }
        return new self($dataCollection, $reflectionEntity, $connection, $mapper);
    }

    public static function createDetachedInstance(){
        return new self();
    }

    public function getRow($id = null, $asArray = false){

        if($this->isDetached()){
            $id = self::DETACHED_ROW_ID;
        }

        return !isset($this->collection[$id]) ? null : new Row($this, $id);
    }

    public function getRecord($rowId){
        if(!isset($this->collection[$rowId])){
            throw new \InvalidArgumentException("Missing row with ID $rowId.");
        }
        return $this->collection[$rowId];
    }

    public function getRecordPointer($rowId){
        if(!isset($this->collection[$rowId])){
            throw new \InvalidArgumentException("Missing row with ID $rowId.");
        }
        return $this->collection[$rowId];
    }

    public function getRecordEntry($rowId, $column){
        if(!isset($this->collection[$rowId])){
            throw new \InvalidArgumentException("Missing row with ID $rowId.");
        }
        if (!array_key_exists($column, $this->collection[$rowId])) {
            throw new \InvalidArgumentException("Missing '$column' column in row with id $rowId.");
        }
        return $this->collection[$rowId][$column];
    }

    public function setRecordEntry($rowId, $column, $value){
        if(!isset($this->collection[$rowId])){
            throw new \InvalidArgumentException("Missing row with ID $rowId.");
        }

        return $this->collection[$rowId][$column] = $value;
    }

    public function hasRecordEntry($id, $column){
        return isset($this->collection[$id]) && array_key_exists($column, $this->collection[$id]);
    }

    public function isDetached(){
        return !$this->attached;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current() {
        $key = current($this->keys);
        return $this->collection[$key];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() {
        next($this->keys);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() {
        return current($this->keys);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() {
        return current($this->keys) !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() {
        $this->keys = array_keys($this->collection);
        reset($this->keys);
    }
}