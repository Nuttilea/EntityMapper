<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/6/18
 * Time: 11:37 PM
 */
namespace Nuttilea\EntityMapper;

use Dibi\Fluent;
use Nette\SmartObject;
use Nuttilea\EntityMapper\Factory\EntityFactory;
use Nuttilea\EntityMapper\Factory\RepositoryFactory;

class Entity
{

    use SmartObject;

    /** @var Row */
    protected $row;

    private $referencedEntities = [];
    private $referenceingEntities = [];

    /** @var []ReflectionEntity */
    protected static $reflections;

    /** @var ReflectionEntity */
    private $currentReflection;

    /** @var boolean */
    private $attached = false;

    /** @var EntityFactory */
    private $entityFactory;

    /** @var Mapper */
    private $mapper;


    /**
     * Repository constructor.
     */
    public function __construct($dataset = null){
        //INIT DATASET
        if($dataset instanceof Row){
            $this->row = $dataset;
        } else {
            $this->row = Result::createDetachedInstance()->getRow();
        }
        if(!empty($dataset)) $this->assign($dataset);
    }

    public function __call($name, $arguments)
    {
        die($name);
    }

    public function __get($name)
    {

        if( strlen($name) > 5 && lcfirst(substr($name, 0,5)) === 'value' ){
            d($name);
            $name = lcfirst(substr($name, 5));
            d("????");
            $prop = $this->getCurrentReflection()->getProp($name);
            return $this->row[$prop->getColumn()];
        }
        $prop = $this->getCurrentReflection()->getProp($name);

        $relationship = $prop->getRelationship();
        if($relationship instanceof HasOne){
            if(!isset($this->referencedEntities[$name])){
                $refRow = $this->row->getReferencedRow($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                $entity->makeAlive($this->entityFactory, $this->mapper);
                $this->referencedEntities[$name] = $entity;
            }
            return $this->referencedEntities[$name];
        }  elseif($relationship instanceof BelongsToOne){
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                $count = count($refRows);
                if ($count > 1) {
                    throw new InvalidValueException(
                        'There cannot be more than one entity referencing to entity ' . get_called_class(
                        ) . " in property '{$prop->getName()}' with m:belongToOne relationship."
                    );
                } else {
                    $refRow = $count === 1 ? array_shift($refRows) : null;
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper);
                    $this->referenceingEntities[$name][] = $entity;
                }
            }
            return $this->referenceingEntities[$name];
        } elseif ($relationship instanceof HasMany){
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                foreach ($refRows as $refRow){
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper);
                    $this->referenceingEntities[$name][] = $entity;
                }
            }
            return $this->referenceingEntities[$name];
        }  elseif($relationship instanceof BelongsToMany){
//            d($name);
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                foreach ($refRows as $refRow){
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper);
                    $this->referenceingEntities[$name][] = $entity;
                }
                if(!isset($this->referenceingEntities[$name])) $this->referenceingEntities[$name] = [];
            }
            return $this->referenceingEntities[$name];
        }
//        $belongsToMany = $this->getCurrentReflection()->getBelongsToMany($name);

        return $this->row[$prop->getColumn()];

    }

    public function __set($name, $value)
    {
        $this->row[$name] = $value;
    }

    public static function getReflection() {
        $class = get_called_class();
        if (!isset(static::$reflections[$class])) {
            static::$reflections[$class] = new ReflectionEntity($class);
        }
        return static::$reflections[$class];
    }


    protected function getCurrentReflection()
    {
        if ($this->currentReflection === null) {
            $this->currentReflection = $this->getReflection();
        }
        return $this->currentReflection;
    }

    public function makeAlive(EntityFactory $entityFactory, Mapper $mapper){
        $this->attached = true;
        $this->entityFactory = $entityFactory;
        $this->mapper = $mapper;
    }

    public function getPrimaryValues(){
        $primaries = $this->getCurrentReflection()->getPrimary();

        if($primaries && is_array($primaries)) {
            $pairs = [];
            foreach ($primaries as $column){
                $pairs[$column] = $this->__get($column);
            }
            return $pairs;
        } elseif ($primaries && is_string($primaries)) {
            return [$primaries => $this->{$primaries}];
        }
        return null;
    }

    public function assign($data, array $whiteList = []){
        $reflection = $this->getCurrentReflection();
        foreach ($data as $column => $value){
            if(!$whiteList || in_array($column, $whiteList)){
                $prop = $reflection->getPropByColumn($column);
                $var = $prop->getVariable();
                if(!$var) throw new \Exception("Column `$column` is not defined!");
                $this->row[$var] = $value;
            }
        }
    }

    public function toArray(){
        return $this->row->toArray();
    }
}

