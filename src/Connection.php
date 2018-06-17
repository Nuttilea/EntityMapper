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

}