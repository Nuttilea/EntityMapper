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

class Entity implements \ArrayAccess {

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
    public function __construct($dataset = []) {
        //INIT DATASET
        if ($dataset instanceof RowPointer) {
            $this->row = $dataset;
        } else {
            $this->row = Result::createDetachedInstance()->getRow();
            if (!empty($dataset)) $this->assign($dataset);
        }
    }

    public function __call($name, $arguments) {
        throw new \BadMethodCallException("Method $name is not exists.");
    }

    //TODO: NEED TO TEST THIS
    public function __set($name, $value) {
        //TODO: RENEMAE NAME TO COLUMN BECAUSE NAME IS NOT COLUMN THEN REWRITE IT
        $property = $this->getCurrentReflection()->getProp($name);
        if (!$property) throw new Exception("Property `$name` isn't exists!");
        $relationship = $property->getRelationship();
        if ($relationship instanceof BelongsToMany) {
            if (!is_array($value)) {
                throw new Exception("Property `$name` needs array!");
            }
            //Only for checkings if all items of arrays are Entities
            foreach ($value as $entity) {
                if (!$entity instanceof Entity) {
                    throw new Exception('Each item of array must be instance of Entity.');
                }
                $this->referenceingEntities[$name][] = $entity;
            }
        } else if ($value instanceof Entity) {
            if ($relationship instanceof HasOne) {
                $this->referencedEntities[$name] = $value;
                foreach ($value->getPrimaryValues() as $key => $value) {
                    if ($key === $name) $this->row[$property->getColumn()] = $value;
                }
            } elseif ($relationship) {
                $this->referenceingEntities[$name] = $value;
            } else {
                $this->row[$property->getColumn()] = $value;
            }
        } else {
            $this->row[$property->getColumn()] = $value;
        }
    }

    public function __get($name) {
//        if (strlen($name) > 5 && substr($name, strlen($name)-5, strlen($name)) === 'Value') {
//            $name = substr($name, 0, strlen($name)-5);
//            $prop = $this->getCurrentReflection()->getProp($name);
//            return $this->row[$prop->getColumn()];
//        }
        $prop = $this->getCurrentReflection()->getProp($name);
        if (!$prop) throw new Exception("Property `$name` is not defined!");

        return $this->get($prop, []);
    }

    private function getHasOneEntity($name, HasOne $relationship, Property $prop) {
        if (!isset($this->referencedEntities[$name])) {
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
    }

    private function getHasManyEntities($name, HasMany $relationship, Property $prop, $filter = []) {
        if (!isset($this->referenceingEntities[$name])) {
            $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn(), $filter);
            foreach ($refRows as $refRow) {
                $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                $this->referenceingEntities[$name][] = $entity;
            }
        }
        return $this->referenceingEntities[$name];
    }

    private function getBelongsToOneEntity($name, BelongsToOne $relationship, Property $prop, $filter = []) {
        if (!isset($this->referenceingEntities[$name])) {
            $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn(), $filter);
            $count = count($refRows);
            if ($count > 1) {
                throw new InvalidValueException(
                    'There cannot be more than one entity refwerencing to entity ' .
                    get_called_class() .
                    " in property '{$prop->getName()}' with m:belongToOne relationship."
                );
            } else if($count === 0){
                $this->referenceingEntities[$name] = null;
            } else {
                $refRow = $count === 1 ? array_shift($refRows) : null;
                $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                $this->referenceingEntities[$name] = $entity;
            }
        }
        return $this->referenceingEntities[$name];
    }

    private function getBelongsToManyEntity($name, BelongsToMany $relationship, Property $prop, $filter = []) {
        if (!isset($this->referenceingEntities[$name])) {
            $refRows = $this->row->getReferencingRows($relationship->getTargetTable(), $relationship->getTargetTableColumn(), $filter);
            foreach ($refRows as $refRow) {
                $entity = $this->entityFactory->create($this->mapper->getEntityReflectionByTable($relationship->getTargetTable())->getName(), $refRow);
                $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                $this->referenceingEntities[$name][] = $entity;
            }
            if (!isset($this->referenceingEntities[$name])) $this->referenceingEntities[$name] = [];
        }
        return $this->referenceingEntities[$name];
    }

    protected function get($property, $filter = []) {
        if($property instanceof Property){
            $name = $property->getVariable();
        } else {
            $name = $property;
            $property = $this->getCurrentReflection()->getProp($property);
        }

        $relationship = $property->getRelationship();
        if ($relationship instanceof HasOne) { //WITHOUT FILTER
            return $this->getHasOneEntity($name, $relationship, $property);
        } elseif ($relationship instanceof BelongsToOne) { //WITH FILTER
            return $this->getBelongsToOneEntity($name, $relationship, $property, $filter);
        } elseif ($relationship instanceof HasMany) { //WITH FILTER
            return $this->getHasManyEntities($name, $relationship, $property, $filter);
        } elseif ($relationship instanceof BelongsToMany) { //WITH FILTER
            return $this->getBelongsToManyEntity($name, $relationship, $property, $filter);
        } else {
            return $this->row->hasColumn($property->getColumn()) ? $this->row[$property->getColumn()] : null;
        }
    }

    public function getPropertyValue($property){
        if($property instanceof Property){
            $name = $property->getVariable();
        } else {
            $name = $property;
            $property = $this->getCurrentReflection()->getProp($property);
        }
        return $this->row->hasColumn($property->getColumn()) ? $this->row[$property->getColumn()] : null;
    }

    public function getColumnValue($column){
        return $this->row->hasColumn($column) ? $this->row[$column] : null;
    }

    protected function getRelatedEntity(Relationship $relationship, $filter = null){
        $parentMethod = Utils::getParentMethod();
        $virtualProperty = new Property($parentMethod);
        $virtualProperty->setColumn($relationship->getColumn());
        $virtualProperty->setRelationship($relationship);
        return $this->get($virtualProperty, !is_array($filter) ? [$filter] : $filter);
    }

    public static function getReflection() {
        $class = get_called_class();
        if (!isset(static::$reflections[$class])) {
            static::$reflections[$class] = new ReflectionEntity($class);
        }
        return static::$reflections[$class];
    }


    public function getCurrentReflection() {
        if ($this->currentReflection === null) {
            $this->currentReflection = $this->getReflection();
        }
        return $this->currentReflection;
    }

    public function attach($id) {
        if ($id) {
            $pv = $this->getPrimaryValues();
            if ($pv && count($pv) === 1) {
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

    public function getTableName(){
        if(!$this->isAttached()){
            $reflClass = new \ReflectionClass(self::class);
            throw new Exception("Entity " . $reflClass->getName() . " is not attached!");
        }
        return $this->mapper->getTableName(get_called_class());
    }

    public function makeAlive(EntityFactory $entityFactory, Mapper $mapper, Connection $connection) {
        $this->attached = true;
        $this->entityFactory = $entityFactory;
        $this->mapper = $mapper;
        $this->connection = $connection;
        $this->row->setMapper($mapper);
        $this->row->setReflectionEntity($this->getCurrentReflection());
        $this->row->setConnection($connection);
    }

    public function getPrimaryValues() {
        $primaries = $this->getCurrentReflection()->getPrimary();
        if ($primaries && is_array($primaries)) {
            $pairs = [];
            foreach ($primaries as $column) {
                $pairs[$column] = $this->__get($column);
            }
            return $pairs;
        } elseif ($primaries && is_string($primaries)) {

            return [$primaries => $this->{$primaries}];
        }
        return null;
    }

    public function assign($data, array $whiteList = []) {
        $reflection = $this->getCurrentReflection();
        foreach ($data as $column => $value) {
            if (!$whiteList || in_array($column, $whiteList)) {
                $prop = $reflection->getPropByColumn($column);
                $var = $prop->getVariable();
                if (!$var) throw new \Exception("Column `$column` is not defined!");
                $this->row[$column] = $value;
            }
        }
    }

    public function isAttached() {
        return $this->row->isAttached();
    }

    public function getReferenced() {
        return $this->referencedEntities;
    }

    public function getReferencing() {
        return $this->referenceingEntities;
    }

    public function toArray($columns = []) {
        $data = $this->row->toArray();
        if($columns){
            $data = array_intersect_key($data, array_flip($columns));
        }
        return $data;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        $property = $this->getCurrentReflection()->getPropByColumn($offset);
        return isset($this->{$property->getVariable()});
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->row[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $property = $this->getCurrentReflection()->getPropByColumn($offset);
        $this->{$property->getVariable()} = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        $property = $this->getCurrentReflection()->getPropByColumn($offset);
        unset($this->{$property->getVariable()});
    }
}

