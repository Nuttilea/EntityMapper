<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/16/18
 * Time: 3:51 PM
 */

namespace Nuttilea\EntityMapper;


use Nuttilea\EntityMapper\Exception\Exception;
use Nuttilea\EntityMapper\Factory\EntityFactory;

class RowPointer implements \ArrayAccess {

    private $rowId;

    /** @var Result */
    private $result;


    public function __construct($id, Result $result) {
        $this->result = $result;
        if(is_array($id)) throw new Exception("`$id` cann't be array!");
        $this->rowId = $id;
    }

    public function __get($name) {
        return $this->result->getRowRecord($this->rowId, $name);
    }

    public function __set($name, $value) {
        $this->result->setRowRecord($this->rowId, $name, $value);
    }

    public function __isset($name) {
        return $this->result->issetRowRecord($this->rowId, $name);
    }

    public function __unset($name) {
        $this->result->unsetRowRecord($this->rowId,$name);
    }

    public function setConnection(Connection $connection){
        $this->result->setConnection($connection);
    }

    public function setMapper(Mapper $mapper){
        $this->result->setMapper($mapper);
    }

    public function setReflectionEntity(ReflectionEntity $reflectionEntity){
        $this->result->setReflectionEntity($reflectionEntity);
    }

    public function attach($id, $table) {
        $this->rowId = $id;
        $this->result->attach($id, $table);
    }

    public function isAttached() {
        return $this->result->isAtached();
    }

    public function toArray() {
        return $this->result->getRowRecords($this->rowId);
    }

    public function getReferencedRow($currentColumn, $targetTable, $targetColumn) {
        return $this->result->getReferencedRow($this->{$currentColumn}, $currentColumn, $targetTable, $targetColumn);
    }

    public function getReferencingRows($table, $viaColumn) {
        return $this->result->getReferencingRows($this->rowId, $table, $viaColumn);
    }

    public function hasColumn($column) {
        return $this->result->issetRowRecord($this->rowId, $column);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return $this->hasColumn($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $this->__set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        $this->__unset($offset);
    }
}