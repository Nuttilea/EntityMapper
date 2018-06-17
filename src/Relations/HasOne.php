<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/17/18
 * Time: 2:01 PM
 */

namespace Nuttilea\EntityMapper;

class HasOne {
    private $targetTableColumn;

    private $targetTable;

    /**
     * HasOne constructor.
     * @param $targetTableColum
     * @param $targetTable
     */
    public function __construct($targetTable, $targetTableColum) {
        $this->targetTableColumn = $targetTableColum;
        $this->targetTable = $targetTable;
    }

    /**
     * @return mixed
     */
    public function getTargetTableColumn() {
        return $this->targetTableColumn;
    }

    /**
     * @return mixed
     */
    public function getTargetTable() {
        return $this->targetTable;
    }

}