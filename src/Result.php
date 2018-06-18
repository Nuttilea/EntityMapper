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

    protected $referenced = [];

    protected $referencing = [];


    /** @var ReflectionEntity */
    protected $reflectionEntity;

    protected $rows = [];

    protected $keys = [];

    protected $index = [];

    /** @var Mapper */
    protected $mapper;


    private function __construct($rows = null, ReflectionEntity $reflectionEntity = null, Connection $connection = null, Mapper $mapper = null) {
        $this->reflectionEntity = $reflectionEntity;
        $this->connection = $connection;
        $this->mapper = $mapper;
        if($rows !== null){
            foreach ($rows as $key => $row){
                $this->rows[$key] = new Row($row, $key, $this);
            }
        } else {
            $this->rows = [self::DETACHED_ROW_ID => new Row([], self::DETACHED_ROW_ID, $this)];
        }
        $this->attached = isset($rows) && $reflectionEntity && $rows && $mapper;
    }

    public static function createAttachedInstance($collection, ReflectionEntity $reflectionEntity, Connection $connection, Mapper $mapper){
//        $primaryKey = implode('#', $reflectionEntity->getPrimaries());
        $primaryKey = $reflectionEntity->getPrimary();
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
        return !isset($this->rows[$id]) ? null : $this->rows[$id];
    }



    public function getReferencedRow($id, $table, $viaColumn) {
        $result = $this->getReferencingResult($table, $viaColumn);
        $rowId = $this->getRow($id)[$viaColumn];
        return $result->getRow($rowId);
    }

    public function getReferencingRows($id, $table, $viaColumn){
        $result = $this->getReferencingResult($table, $viaColumn);
        $resultHash = spl_object_hash($result);
        if(!isset($this->index[$resultHash])){
            $this->index[$resultHash] = [];
            foreach ($result as $internalID => $row){
                $this->index[$resultHash][$row[$viaColumn]][] = new Row($row, $internalID, $result);
            }
        }
        if (!isset($this->index[$resultHash][$id])) {
            return [];
        }
        return $this->index[$resultHash][$id];
    }

    /** @return Result */
    public function getReferencingResult($table, $viaColumn){
        $key = "$table{$viaColumn}";
        if(isset($this->referencing[$key])){
            return $this->referencing[$key];
        }
        $reflectionEntity = $this->mapper->getEntityReflectionByTable($table);

        $res = $this->connection->findIn($table, $viaColumn, $this->getIds($reflectionEntity->getPrimary()));
        return $this->referencing[$key] = self::createAttachedInstance($res, $reflectionEntity, $this->connection, $this->mapper);
    }

    /** @return Result */
    public function getReferencedResult($table, $viaColumn){
        $key = "$table{$viaColumn}";
        if(isset($this->referenced[$key])){
            return $this->referenced[$key];
        }

        $reflectionEntity = $this->mapper->getEntityReflectionByTable($table);
        $res = $this->connection->findIn($table, $reflectionEntity->getPrimary(), $this->getIds($viaColumn));
        return $this->referenced[$key] = self::createAttachedInstance($res, $reflectionEntity, $this->connection, $this->mapper);
    }

    /**
     * @param string $column
     * @return array
     */
    private function getIds($column)
    {
        $ids = [];
        foreach ($this->rows as $item) {
            if (!isset($item[$column]) or $item[$column] === null) {
                continue;
            }
            $ids[$item[$column]] = true;
        }
        return array_keys($ids);
    }

//    public function getRecordEntry($rowId, $column){
//        if(!isset($this->rows[$rowId])){
//            throw new \InvalidArgumentException("Missing row with ID (`$column`) $rowId.");
//        }
//        if (!array_key_exists($column, $this->rows[$rowId])) {
//            throw new \InvalidArgumentException("Missing '$column' column in row with ID $rowId.");
//        }
//        return $this->rows[$rowId][$column];
//    }

//    public function setRecordEntry($rowId, $column, $value){
//        if(!isset($this->rows[$rowId])){
//            throw new \InvalidArgumentException("Missing row with ID $rowId.");
//        }
//
//        return $this->rows[$rowId][$column] = $value;
//    }
//
//    public function hasRecordEntry($id, $column){
//        return isset($this->rows[$id]) && array_key_exists($column, $this->rows[$id]);
//    }

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
        return $this->rows[$key];
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
        $this->keys = array_keys($this->rows);
        reset($this->keys);
    }
}