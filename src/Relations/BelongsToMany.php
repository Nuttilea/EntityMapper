<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/17/18
 * Time: 2:01 PM
 */

namespace Nuttilea\EntityMapper;

class BelongsToMany
{
    private $targetTableColumn;
    /** @var string|null */
    private $targetTable;

    /**
     * @param string|null $targetTableColumn
     * @param string|null $targetTable
     * @param string $strategy
     */
    public function __construct($targetTable, $targetTableColumn)
    {
        $this->targetTableColumn = $targetTableColumn;
        $this->targetTable = $targetTable;
    }

    /**
     * Gets name of column referencing target table
     *
     * @return string|null
     */
    public function getTargetTableColumn()
    {
        return $this->targetTableColumn;
    }

    /**
     * Gets name of target table
     *
     * @return string|null
     */
    public function getTargetTable()
    {
        return $this->targetTable;
    }

}