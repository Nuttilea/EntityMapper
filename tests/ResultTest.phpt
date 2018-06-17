<?php

namespace Test;

use Nette;
use Nuttilea\EntityMapper\Connection;
use Nuttilea\EntityMapper\Mapper;
use Nuttilea\EntityMapper\ReflectionEntity;
use Nuttilea\EntityMapper\Result;
use Test\classes\Tags;
use Tester;

$container = require __DIR__ . '/bootstrap.php';


/**
 * @testCase
 *
 */
class ResultTest extends Tester\TestCase
{
    private $container;

    /** @var Mapper */
    private $mapper;

    /** @var Connection */
    private $connection;

    /** @var ResultTest */
    private $results;

    private $data = [
        ['ID' => 100, 'tag' => 'a'],
        ['ID' => 200, 'tag' => 'b'],
        ['ID' => 300, 'tag' => 'c'],
        ['ID' => 400, 'tag' => 'd'],
    ];

    public function __construct(Nette\DI\Container $container)
    {
        $this->container = $container;
        $this->mapper = new Mapper();
        $dibi = $container->getByType(\Dibi\Connection::class);
        $this->connection = new Connection($dibi);
    }


    public function setUp(){

    }

    public function testSetupResult(){
        $result = Result::createDetachedInstance();

        //Setup
        foreach ($this->data as $data){
            $row = $result->getRow();
            foreach ($data as $col => $val){
                $row->{$col} = $val;
            }
        }

        Tester\Assert::equal($result->getRowEntry(100, 'tag'), 'a');
        Tester\Assert::equal($result->getRowEntry(200, 'tag'), 'b');
        Tester\Assert::equal($result->getRowEntry(300, 'tag'), 'c');
        Tester\Assert::equal($result->getRowEntry(400, 'tag'), 'd');
    }


}

(new ResultTest($container))->run();
