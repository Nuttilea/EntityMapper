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
use Nuttilea\EntityMapper\RowPointer;

class EntityFactory {

    /**
     * RepositoryFactory constructor.
     * @param Mapper $mapper
     */
    public function __construct() {
    }

    /**
     * @param string $entityName
     * @return Entity
     * @throws FactoryException
     */
    public function create(string $entityName, $data = []) {
        if (is_subclass_of(Entity::class, $entityName)) throw new FactoryException("$entityName is not instance of " . Entity::class . '.');

        /** @var Entity $entity */
        $entity = null;
        try {
            $reflection = new \ReflectionClass($entityName);
            if($data && !empty($data)){
                $entity = $reflection->newInstance($data);
            }
        } catch (\ReflectionException $reflectionException) {
            throw new FactoryException("Can't create instance of $entityName.");
        }
        return $entity;
    }

}