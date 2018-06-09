<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 4/2/18
 * Time: 3:56 PM
 */
namespace Nuttilea\EntityMapper\Bridges\Nette\DI;

use Nette\Caching\Storages\DevNullStorage;
use Nette\DI\CompilerExtension;
use Nette\Loaders\RobotLoader;
use Nuttilea\EntityMapper\Factory\EntityFactory;
use Nuttilea\EntityMapper\Factory\RepositoryFactory;
use Nuttilea\EntityMapper\Mapper;
use Nuttilea\EntityMapper\Repository;

class EntityMapperExtension extends CompilerExtension {

    public $defaults = [
        'db' => [],
        'profiler' => true,
        'scanDirs' => null,
        'logFile' => null,
    ];

    /**
     * Returns extension configuration.
     * @return array
     */
    public function getConfig()
    {
        $container = $this->getContainerBuilder();
        $this->defaults['scanDirs'] = $container->expand('%appDir%');
        return parent::getConfig($this->defaults);
    }



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();
        $config = $this->getConfig();

        $index = 1;
        foreach ($this->findRepositories($config) as $repositoryClass) {
            $container->addDefinition($this->prefix('table.' . $index++))->setClass($repositoryClass);
        }

        $container->addDefinition($this->prefix('mapper'))
            ->setClass(Mapper::class);

        $container->addDefinition($this->prefix('entityFactory'))
            ->setClass(EntityFactory::class);

        $container->addDefinition($this->prefix('repositoryFactory'))
            ->setClass(RepositoryFactory::class);

    }



    private function findRepositories($config)
    {
        $classes = [];

        if ($config['scanDirs']) {
            $robot = new RobotLoader();
            //$robot->setCacheStorage(new DevNullStorage());
            $robot->addDirectory($config['scanDirs']);
            $robot->acceptFiles = '*.php';
            $robot->rebuild();
            $classes = array_keys($robot->getIndexedClasses());
        }

        $repositories = [];
        foreach (array_unique($classes) as $class) {
            if (class_exists($class)
                && ($rc = new \ReflectionClass($class)) && $rc->isSubclassOf(Repository::class)
                && !$rc->isAbstract()
            ) {
                $repositories[] = $rc->getName();
            }
        }
        return $repositories;
    }
}