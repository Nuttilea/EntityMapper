<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 7/21/18
 * Time: 11:55 PM
 */

namespace Nuttilea\EntityMapper;


abstract class Relationship {

    private $table;

    private $column;

    private $targetTable;

    private $targetTableColumn;

    /**
     * Relationship constructor.
     * @param $table
     * @param $viaTableColumn
     * @param $targetTable
     * @param $targetTableColumn
     */
    public function __construct($table, $viaTableColumn, $targetTable, $targetTableColumn) {
        $this->table = $table;
        $this->column = $viaTableColumn;
        $this->targetTable = $targetTable;
        $this->targetTableColumn = $targetTableColumn;
    }

    /**
     * @return mixed
     */
    public function getTable() {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table) {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getColumn() {
        return $this->column;
    }

    /**
     * @param mixed $column
     */
    public function setColumn($column) {
        $this->column = $column;
    }

    /**
     * @return mixed
     */
    public function getTargetTable() {
        return $this->targetTable;
    }

    /**
     * @param mixed $targetTable
     */
    public function setTargetTable($targetTable) {
        $this->targetTable = $targetTable;
    }

    /**
     * @return mixed
     */
    public function getTargetTableColumn() {
        return $this->targetTableColumn;
    }

    /**
     * @param mixed $targetTableColumn
     */
    public function setTargetTableColumn($targetTableColumn) {
        $this->targetTableColumn = $targetTableColumn;
    }

}