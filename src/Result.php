<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/16/18
 * Time: 3:51 PM
 */

namespace Nuttilea\EntityMapper;


use Nette\InvalidStateException;
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
                $this->rows[$key] = $row;
            }
        } else {
            $this->rows = [self::DETACHED_ROW_ID => []];
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

    public function getRowRecord($rowId, $name){
        return $this->rows[$rowId][$name];
    }

    public function setRowRecord($rowId, $name, $value){
        $this->rows[$rowId][$name] = $value;
    }

    public function issetRowRecord($rowId, $name){
        return isset($this->rows[$rowId]) && isset($this->rows[$rowId][$name]);
    }

    public function unsetRowRecord($rowId, $name){
        unset($this->rows[$rowId][$name]);
    }

    public function getRowRecords($rowId){
        return $this->rows[$rowId];
    }

    public function getRow($id = null, $asArray = false){
        if(!$this->isAtached()){
            $id = self::DETACHED_ROW_ID;
        }
        return !isset($this->rows[$id]) ? null : new RowPointer($id, $this);
    }

    public function getReferencedRow($id, $currentColumn, $table, $targetColumn, $filter = []) {
        $result = $this->getReferencedResult($table, $currentColumn, $filter);
        $rowId = $result->getRow($id)[$targetColumn];
        return $result->getRow($rowId);
    }

    public function getReferencingRows($id, $table, $viaColumn, $filter = []){
        $result = $this->getReferencingResult($table, $viaColumn, $filter);
        $resultHash = spl_object_hash($result);
        if(!isset($this->index[$resultHash])){
            $this->index[$resultHash] = [];
            foreach ($result as $referencingId => $row){
                $this->index[$resultHash][$row[$viaColumn]][] = new RowPointer($referencingId, $result);
            }
        }
        if (!isset($this->index[$resultHash][$id])) {
            return [];
        }
        return $this->index[$resultHash][$id];
    }

    /** @return Result */
    public function getReferencingResult($table, $viaColumn, $filter = []){
        $key = "$table{$viaColumn}";
        if(isset($this->referencing[$key])){
            return $this->referencing[$key];
        }
        $reflectionEntity = $this->mapper->getEntityReflectionByTable($table);
        $res = $this->connection->findIn($table, $viaColumn, $this->getIds($this->reflectionEntity->getPrimary()), $filter);
        return $this->referencing[$key] = self::createAttachedInstance($res, $reflectionEntity, $this->connection, $this->mapper);
    }

    /** @return Result */
    public function getReferencedResult($table, $viaColumn, $filter = []){
        $key = "$table#{$viaColumn}";
        if(isset($this->referenced[$key])){
            return $this->referenced[$key];
        }
        $reflectionEntity = $this->mapper->getEntityReflectionByTable($table);
        $res = $this->connection->findIn($table, $reflectionEntity->getPrimary(), $this->getIds($viaColumn), $filter);
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


    public function setConnection(Connection $connection){
        $this->connection = $connection;
    }

    public function setMapper(Mapper $mapper){
        $this->mapper = $mapper;
    }

    public function setReflectionEntity(ReflectionEntity $reflectionEntity){
        $this->reflectionEntity = $reflectionEntity;
    }

    public function makeAlive(Mapper $mapper, Connection $connection) {
        $this->mapper = $mapper;
        $this->connection = $connection;
    }

    public function attach($id, $tableName){
        if ($this->isAtached()) {
            throw new InvalidStateException('Result is not in detached state.');
        }
        if ($this->connection === null) {
            throw new InvalidStateException('Missing connection.');
        }
        if ($this->mapper === null) {
            throw new InvalidStateException('Missing mapper.');
        }

        $this->rows[$id] = $this->rows[-1];
        unset($this->rows[-1]);
        $this->attached = true;

    }

    public function isAtached(){
        return $this->attached;
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