<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 7/12/18
 * Time: 2:51 PM
 */

namespace Test\classes;


use Nuttilea\EntityMapper\Connection;

class TestConnection extends Connection {

    public function findIn($table, $column, $ids) {
        return parent::findIn($table, $column, $ids); // TODO: Change the autogenerated stub
    }
}