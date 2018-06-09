<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 3/26/18
 * Time: 7:25 PM
 */

namespace Nuttilea\EntityMapper\Factory;

use Nuttilea\EntityMapper\Exception\FactoryException;
use Nuttilea\EntityMapper\Mapper;
use Nuttilea\EntityMapper\Repository;

class RepositoryFactory {

    /** @var \Dibi\Connection */
    private $dibi;

    /** @var Mapper */
    private $mapper;
    private $entityFactory;

    /**
     * RepositoryFactory constructor.
     * @param \Dibi\Connection $dibi
     * @param Mapper $mapper
     * @param EntityFactory $entityFactory
     */
    public function __construct(\Dibi\Connection $dibi, Mapper $mapper, EntityFactory $entityFactory) {
        $this->dibi = $dibi;
        $this->mapper = $mapper;
        $this->entityFactory = $entityFactory;
    }

    /**
     * @param string $repositoryName
     * @return Repository
     * @throws FactoryException
     */
    public function create(string $repositoryName) {
        if (is_subclass_of(Repository::class, $repositoryName)) throw new FactoryException("$repositoryName is not instance of " . Repository::class . '.');
        try {
            $reflection = new \ReflectionClass($repositoryName);
            /** @var Repository $repository */
            $repository = $reflection->newInstance($this->dibi, $this->mapper, $this->entityFactory);
            return $repository;

        } catch (\ReflectionException $reflectionException) {
            throw new FactoryException("Can't create instance of $repositoryName.");
        }
    }
}