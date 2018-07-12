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
use Nuttilea\EntityMapper\Exception\Exception;
use Nuttilea\EntityMapper\Factory\EntityFactory;
use Nuttilea\EntityMapper\Factory\RepositoryFactory;

class Entity
{

    use SmartObject;

    /** @var RowPointer */
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

    private $connection;

    /**
     * Repository constructor.
     */
    public function __construct($dataset = null) {
        //INIT DATASET
        if($dataset instanceof RowPointer){
            $this->row = $dataset;
        } else {

            $this->row = Result::createDetachedInstance()->getRow();
            if(!empty($dataset)) $this->assign($dataset);
        }
    }

    public function __call($name, $arguments) {
        die($name);
    }

    //TODO: NEED TO TEST THIS
    public function __set($name, $value)
    {
        //TODO: RENEMAE NAME TO COLUMN BECAUSE NAME IS NOT COLUMN THEN REWRITE IT
        $property = $this->getCurrentReflection()->getProp($name);
        if(!$property) throw new Exception("Property `$name` isn't exists!");
        $relationship = $property->getRelationship();
        if($relationship instanceof BelongsToMany){
            if(!is_array($value)) {
                throw new Exception("Property `$name` needs array!");
            }
            //Only for checkings if all items of arrays are Entities
            foreach ($value as $entity) {
                if(!$entity instanceof Entity){
                    throw new Exception('Each item of array must be instance of Entity.');
                }
                $this->referenceingEntities[$name][] = $entity;
            }
        } else if($value instanceof Entity) {
            if($relationship instanceof HasOne) {
                $this->referencedEntities[$name] = $value;
                foreach ($value->getPrimaryValues() as $key => $value){
                    if($key === $name) $this->row[$property->getColumn()] = $value;
                }
            } elseif($relationship){
                $this->referenceingEntities[$name] = $value;
            } else {
                $this->row[$property->getColumn()] = $value;
            }
        } else {
            $this->row[$property->getColumn()] = $value;
        }
    }

    public function __get($name) {
        if( strlen($name) > 5 && lcfirst(substr($name, 0,5)) === 'value' ){
            d($name);
            $name = lcfirst(substr($name, 5));
            d("????");
            $prop = $this->getCurrentReflection()->getProp($name);
            return $this->row[$prop->getColumn()];
        }
        $prop = $this->getCurrentReflection()->getProp($name);
        if(!$prop) throw new Exception("Property `$name` is not defined!");
        $relationship = $prop->getRelationship();
        if($relationship instanceof HasOne){
            if(!isset($this->referencedEntities[$name])){
                //Fix missing target id
                $targetColumn = $relationship->getTargetTableColumn();
                $targetColumn ?: $targetColumn = $this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getPrimary();
                //Try to find item
                $refRow = $this->row->getReferencedRow($prop->getColumn(), $relationship->getTargetTable(), $targetColumn);
                $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                $this->referencedEntities[$name] = $entity;
            }
            return $this->referencedEntities[$name];
        }  elseif($relationship instanceof BelongsToOne){
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                $count = count($refRows);
                if ($count > 1) {
                    throw new InvalidValueException(
                        'There cannot be more than one entity refwerencing to entity ' . get_called_class(
                        ) . " in property '{$prop->getName()}' with m:belongToOne relationship."
                    );
                } else {
                    $refRow = $count === 1 ? array_shift($refRows) : null;
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                    $this->referenceingEntities[$name] = $entity;
                }
            }
            return $this->referenceingEntities[$name];
        } elseif ($relationship instanceof HasMany){
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                foreach ($refRows as $refRow){
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                    $this->referenceingEntities[$name][] = $entity;
                }
            }
            return $this->referenceingEntities[$name];
        }  elseif($relationship instanceof BelongsToMany){
            if(!isset($this->referenceingEntities[$name])){
                $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn());
                foreach ($refRows as $refRow){
                    $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                    $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                    $this->referenceingEntities[$name][] = $entity;
                }
                if(!isset($this->referenceingEntities[$name])) $this->referenceingEntities[$name] = [];
            }
            return $this->referenceingEntities[$name];
        }
//        $belongsToMany = $this->getCurrentReflection()->getBelongsToMany($name);
//        d($this->row->hasColumn($prop->getColumn()));
//        d($this->row);
//        d($this->row->hasColumn($prop->getColumn()));
        return $this->row->hasColumn($prop->getColumn()) ? $this->row[$prop->getColumn()] : null;

    }


    public static function getReflection() {
        $class = get_called_class();
        if (!isset(static::$reflections[$class])) {
            static::$reflections[$class] = new ReflectionEntity($class);
        }
        return static::$reflections[$class];
    }


    public function getCurrentReflection()
    {
        if ($this->currentReflection === null) {
            $this->currentReflection = $this->getReflection();
        }
        return $this->currentReflection;
    }

    public function attach($id){
        if($id) {
            $pv = $this->getPrimaryValues();
            if($pv && count($pv) === 1){
                $pv = array_combine(array_keys($pv), [$id]);
                $this->assign($pv);
                $this->row->attach($id, $this->mapper->getTableName(get_called_class()));
            } else {
                throw new Exception("Entity must have exactly one primary key.");
            }
        } else {
            throw new Exception('Parameter $id can\'t be null value.');
        }
    }

    public function makeAlive(EntityFactory $entityFactory, Mapper $mapper, Connection $connection){
        $this->attached = true;
        $this->entityFactory = $entityFactory;
        $this->mapper = $mapper;
        $this->connection = $connection;
        $this->row->setMapper($mapper);
        $this->row->setReflectionEntity($this->getCurrentReflection());
        $this->row->setConnection($connection);
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
                $this->row[$column] = $value;
            }
        }
    }

    public function isAttached(){
        return $this->row->isAttached();
    }

    public function getReferenced(){
        return $this->referencedEntities;
    }

    public function getReferencing(){
        return $this->referenceingEntities;
    }

    public function toArray(){
        return $this->row->toArray();
    }
}

