<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/11/18
 * Time: 12:41 PM
 */
namespace Nuttilea\EntityMapper;

class Connection
{
    /** @var \Dibi\Connection */
    public $dibi;


    /**
     * Connection constructor.
     * @param \Dibi\Connection $dibi
     * @param $mapper
     */
    public function __construct(\Dibi\Connection $dibi)
    {
        $this->dibi = $dibi;
    }

    public function findIn($table, $column, $ids){

//        d($this->dibi->select('%n.*', $table)
//            ->from('%n', $table)
//            ->where('%n.%n IN %in ', $table, $column, $ids)
//            ->test());

        return $this->dibi->select('%n.*', $table)
            ->from('%n', $table)
            ->where('%n.%n IN %in', $table, $column, $ids)
            ->execute()->setRowClass(null)->fetchAll();
    }

}