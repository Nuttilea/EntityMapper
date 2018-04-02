<?php
/**
 * Created by PhpStorm.
 * User: tonda
 * Date: 2/11/18
 * Time: 12:41 PM
 */
namespace Nutillea\EntityMapper;

class Connection
{
    /** @var \Dibi\Connection */
    public $dibi;

    public $defaultMapper;

    /**
     * Connection constructor.
     * @param \Dibi\Connection $dibi
     * @param $mapper
     */
    public function __construct(\Dibi\Connection $dibi, $mapper = null)
    {
        $this->dibi = $dibi;
        $this->defaultMapper = $mapper ? $mapper : new \Nutillea\Mapper();
    }

}