<?php

namespace Test;

use Nuttilea\IMapper;
use Nuttilea\Mapper;
use Nuttilea\MapperV2;
use Nuttilea\ReflectionEntity;
use Nette;
use Test\classes\Tags;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';


/**
 * @testCase
 *
 */
class MapperTest extends Tester\TestCase
{
	private $container;

	/** @var Mapper */
	private $mapper;

	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}


	public function setUp(){
        $this->mapper = new Mapper();
    }

    public function testGetTableName(){
        Assert::equal('Tags', $this->mapper->getTableName('Tags'));
        Assert::equal('User', $this->mapper->getTableName('User'));
        Assert::equal('UserHasTags', $this->mapper->getTableName('UserHasTags'));
    }

    public function testGetEntityName(){
        Assert::equal('Tags', $this->mapper->getEntityName('tags'));
        Assert::equal('User', $this->mapper->getEntityName('user'));
        Assert::equal('UserHasTags', $this->mapper->getEntityName('userHasTags'));
    }

    public function testGetColumnVariable(){
       $reflection =  new ReflectionEntity(Tags::class);
       Assert::equal('id', $this->mapper->getColumnVariable($reflection, 'ID'));
       Assert::equal('tag', $this->mapper->getColumnVariable($reflection, 'tag'));
    }

    public function testGetPrimary(){
        Assert::equal(['ID'], $this->mapper->getPrimary(Tags::class));
    }

    public function testGetTebaleNameByRepository(){
	    Assert::equal('Tags', $this->mapper->getTableByRepositoryClass('A\B\C\TagsRepository'));
        Assert::equal('Users', $this->mapper->getTableByRepositoryClass('A\B\C\UsersRepository'));
        Assert::equal('UserHasTags', $this->mapper->getTableByRepositoryClass('A\B\C\UserHasTagsRepository'));
    }

}

(new MapperTest($container))->run();
