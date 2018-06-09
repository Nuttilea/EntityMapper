<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 3/26/18
 * Time: 7:25 PM
 */
namespace Nuttilea\EntityMapper\Factory;

use Nuttilea\EntityMapper\Entity;
use Nuttilea\EntityMapper\Exception\FactoryException;
use Nuttilea\EntityMapper\Mapper;

class EntityFactory {

    /** @var Mapper */
    private $mapper;

    /**
     * RepositoryFactory constructor.
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper) {
        $this->mapper = $mapper;
    }

    /**
     * @param string $entityName
     * @return Entity
     * @throws FactoryException
     */
    public function create(string $entityName, $data = []) {
        if (is_subclass_of(Entity::class, $entityName)) throw new FactoryException("$entityName is not instance of " . Entity::class . '.');
        try {
            $reflection = new \ReflectionClass($entityName);
            /** @var Entity $entity */
            $entity = $reflection->newInstance($this->mapper, $data);
            return $entity;
        } catch (\ReflectionException $reflectionException) {
            throw new FactoryException("Can't create instance of $entityName.");
        }
    }

}