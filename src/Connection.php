<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/11/18
 * Time: 12:41 PM
 */
namespace Nuttilea\EntityMapper;

use Dibi\Fluent;

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

    public function findIn($table, $column, $ids, $filter = []){



//        d($this->dibi->select('%n.*', $table)
//            ->from('%n', $table)
//            ->where('%n.%n IN %in ', $table, $column, $ids)
//            ->test());

        $fluent = $this->dibi->select('%n.*', $table)
            ->from('%n', $table)
            ->where('%n.%n IN %in', $table, $column, $ids);
        $fluent = $this->applyFilter($fluent, $filter);
        return $fluent->execute()->setRowClass(null)->fetchAll();
    }

    protected function applyFilter(Fluent $fluent, $filters = []){
        foreach ($filters as $filter){
            if(is_callable($filter)){
                $f = call_user_func($filter, $fluent);
                if($f) $fluent = $f;
            }
        }
        return $fluent;
    }

}