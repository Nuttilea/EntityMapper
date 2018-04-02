<?php
/**
 * Created by PhpStorm.
 * User: tonda
 * Date: 3/11/18
 * Time: 8:44 PM
 */

namespace Nutillea\EntityMapper\Generator;

use Nette\PhpGenerator\PhpFile;
use Nutillea\EntityMapper\Entity;
use Nutillea\EntityMapper\Mapper;

class EntityGenerator {

    private $namespace = "Model";

    /** @var Mapper */
    private $mapper = null;

    private $schema = ['cols' => [], 'table' => null, 'primary'=> []];

    /**
     * EntityGenerator constructor.
     * @param Mapper $mapper
     * @param string $namespace
     */
    public function __construct(Mapper $mapper, $namespace = 'Model') {
        $this->mapper = $mapper;
        $this->namespace = $namespace;
    }


    public function addColumn($name, $variableName = null, $primary = false) {
        $this->schema['cols'][$name] = $variableName;
        if($primary) $this->schema['primary'][$name] = true;
        return $this;
    }

    public function setTableName($name) {
        $this->schema['table'] = $name;
        return $this;
    }

    public function setMapper($mapper) {
        $this->mapper = $mapper;
        return $this;
    }

    public function getName(){
        return $this->mapper->getEntityName($this->schema['table']);
    }

    /** @return string */
    public function __toString() {
        $php = new PhpFile();
        $namespace = $php->addNamespace($this->namespace);
        $namespace->addUse(Mapper::class);
        $class = $namespace->addClass($this->getName());
        $class->addExtend(Entity::class);
        //Annotation generator
        $annotationGenerator = new AnnotationGenerator();
        foreach ($this->schema['cols'] as $col => $value){
            $orm = "orm:column";
            if(array_key_exists($col ,$this->schema['primary'])) $orm .= ' orm:primary';

            $annotationGenerator->addAnnotation('property', '$'.$col, $orm );
        }
        $class->addComment($annotationGenerator);
        return $php->__toString();
    }

}