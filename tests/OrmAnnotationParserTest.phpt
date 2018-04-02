<?php

namespace Test;

use Nutillea\OrmAnotationPareser;
use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';


/**
 * @testCase
 *
 * @property int $id orm:column(ID)
 * @property int $tag orm:column
 * @property string $name orm:belongsTo(user_name)
 *
 */
class OrmAnnotationParserTest extends Tester\TestCase
{
	private $container;


	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}


	public function setUp(){

    }



	public function testSimpleAnnotations()
	{
        $annotations = Nette\Reflection\AnnotationsParser::getAll(new \ReflectionClass(self::class));
        $r = OrmAnotationPareser::parseOrmPropertiesTags($annotations['property']);
        Assert::equal([
            'id' => [
                'column' => 'ID',
                'type' => 'int'
            ],
            'tag' => [
                'column' => true,
                'type' => 'int'
            ],
            'name' => [
                'belongsTo'=>'user_name',
                'type' => 'string'
            ]
        ], $r);

    }
}


$test = new OrmAnnotationParserTest($container);
$test->run();
