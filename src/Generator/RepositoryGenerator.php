<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 4/2/18
 * Time: 11:38 AM
 */

namespace Nutillea\EntityMapper\Generator;


use Nette\PhpGenerator\PhpFile;
use Nutillea\EntityMapper\Mapper;
use Nutillea\EntityMapper\Repository;

class RepositoryGenerator {

    private $tableName;

    private $namespace;

    /** @var Mapper */
    private $mapper;

    /**
     * RepositoryGenerator constructor.
     * @param $tableName
     * @param $namespace
     * @param Mapper $mapper
     */
    public function __construct($tableName, Mapper $mapper, $namespace = 'Model') {
        $this->tableName = $tableName;
        $this->namespace = $namespace;
        $this->mapper = $mapper;
    }

    public function getName(){
        return $this->mapper->getEntityName($this->tableName).'Repository';
    }

    /**
     * @return string
     */
    public function __toString() {
        $phpFile = new PhpFile();
        $namespace = $phpFile->addNamespace($this->namespace);
        $class = $namespace->addClass($this->getName());
        $class->addExtend(Repository::class);
        return $phpFile->__toString();
    }
}