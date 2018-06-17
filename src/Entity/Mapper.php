<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 3/11/18
 * Time: 10:16 PM
 */

namespace Nuttilea\EntityMapper;


class Mapper {

    public $namespace = 'App\Model\Database\Entities\\';

    /** @var ReflectionEntity[] */
    public static $reflectionsEntity = [];

    /**
     * @param string $entityClass
     * @return ReflectionEntity
     */
    public function getReflectionEntity($entityClass) {
        if(!array_key_exists($entityClass, self::$reflectionsEntity)){
            self::$reflectionsEntity[$entityClass] = new ReflectionEntity($entityClass);
        }
        return self::$reflectionsEntity[$entityClass];
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getTableName(string $entityName) {
        $entityName = $this->trimNamespace($entityName);
        return ucfirst($entityName);
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function getEntityName(string $tableName) {
        return ucfirst($tableName);
    }

    public function getRepositoryByEntityClass(string $entityClass){
        $simpleEntityName = $this->trimNamespace($entityClass);
        $entityName = $this->namespace.$simpleEntityName.'Repository';
        return $entityName;
    }

    public function getEntityByRepositoryClass(string $repositoryClass){
        $simpleRepositoryName = $this->trimNamespace($repositoryClass);
        $entityName = $this->namespace.preg_replace('~Repository~', '', $simpleRepositoryName);
        return $entityName;
    }

    /**
     * @param ReflectionEntity $reflectionClass
     * @param string $field
     * @return string
     */
    public function getColumnVariable(ReflectionEntity $reflectionClass, string $field) {
        return $reflectionClass->getColumnVariable($field);
    }

    /**
     * @param string $entityClass
     * @return array
     */
    public function getPrimaries($entityClass) {
        return $this->getReflectionEntity($entityClass)->getPrimaries();
    }

    public function getPrimary($entityClass){
        return $this->getReflectionEntity($entityClass)->getPrimary();
    }

    public function getEntityReflectionByTable($table){
        return $this->getReflectionEntity($this->namespace.$this->getEntityName($table));
    }

    public function getPrimaryByTable($table){
        $entityClass = $this->getEntityName($table);
        $p = $this->getPrimaries($this->namespace.$entityClass);
        return array_shift($p);
    }

    /**
     * @param string $repositoryClass
     * @return string
     */
    public function getTableByRepositoryClass(string $repositoryClass){
        $simpleRepositoryName = $this->trimNamespace($repositoryClass);
        $entityName = $this->namespace.preg_replace('~Repository~', '', $simpleRepositoryName);
        return $this->getTableName($entityName);
    }

    public function trimNamespace($class){
        $r = explode('\\', $class);
        return end($r);
    }


}