<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/6/18
 * Time: 11:38 PM
 */

namespace Nuttilea\EntityMapper;

use Dibi\Fluent;
use Nuttilea\EntityMapper\Exception\Exception;
use Nuttilea\EntityMapper\Factory\EntityFactory;
use Nuttilea\Utils\ArrayUtils;

class Repository {
    use \Nette\SmartObject;

    /** @var \Dibi\Connection */
    public $dibi;

    /** @var Connection */
    public $connection;

    /** @var  Mapper */
    public $mapper;

    /** @var EntityFactory */
    public $entityFactory;

    public $entityClass = null;

    /** @var string */
    public $tableName = null;


    /**
     * Repository constructor.
     * @param \Dibi\Connection $dibi
     * @param Mapper $mapper
     * @param EntityFactory $entityFactory
     */
    public function __construct(\Dibi\Connection $dibi, Mapper $mapper = null, EntityFactory $entityFactory = null) {
        $this->dibi = $dibi;
        $this->connection = new Connection($dibi);
        $this->mapper = $mapper ? $mapper : new Mapper();
        $this->entityFactory = $entityFactory ? $entityFactory : new EntityFactory($this->mapper);
    }


    public function getEntityClass() {
        if (!$this->entityClass) $this->entityClass = $this->mapper->getEntityByRepositoryClass(get_called_class());
        return $this->entityClass;
    }

    public function getTableName() {
        if (!$this->tableName) {
            $this->tableName = $this->mapper->getTableByRepositoryClass(get_called_class());
        }
        return $this->tableName;
    }

    // THIS IS FOR 1:M
    //    protected function createSelection(){
    //        $reflection = $this->mapper->getReflectionEntity();
    //        if($hasMany = $reflection->getHasMany()){
    //            $selection = $this->dibi->select('*');
    //            $selection->leftJoin();
    //        }
    //        return $selection;
    //    }

    public function createEntities(array $data) {
        $ret = [];
        $className = $this->mapper->getEntityByRepositoryClass(get_called_class());
        $result = Result::createAttachedInstance($data, $this->mapper->getReflectionEntity($className), $this->connection, $this->mapper);
        foreach ($data as $item) {
            $primaryKey = $this->mapper->getPrimary($className);
            $row = $result->getRow($item[$primaryKey]);
            $entity = $this->entityFactory->create($this->getEntityClass(), $row);
            $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
            $ret[] = $entity;
        }
        return $ret;
    }

    public function createEntity($data) {
        if (!$data) return null;
        $className = $this->mapper->getEntityByRepositoryClass(get_called_class());
        $primaryKey = $this->mapper->getPrimary($className);
        $result = Result::createAttachedInstance([$data], $this->mapper->getReflectionEntity($className), $this->connection, $this->mapper);
        $row = $result->getRow($data[$primaryKey]);
        $entity = $this->entityFactory->create($this->getEntityClass(), $row);
        $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
        return $entity;
    }

    /**
     * $this->delete(Entity)
     * $this->delete([id => 1])
     * $this->delete(1)
     *
     * @param $where array
     * @return bool
     * @throws Exception
     * @throws \Dibi\Exception
     */
    public function delete($where = null) {
        if ($where instanceof Entity) {
            $primaries = $where->getPrimaryValues();
        } elseif (is_array($where) && array_keys($where) === range(0, count($where) - 1)) {
            throw new Exception("Expected associative array [column => value]");
        } else if (!is_array($where)) {
            $primaries = $this->mapper->getPrimaries($this->getEntityClass());
            if (count($primaries) !== 1) throw new Exception('Entity has multiple primary...');
            $where = [$primaries[0] => $where];
        }
        $this->dibi->delete($this->getTableName())->where($where)->execute();
        return true;
    }

    public function persist($entity){
        if($entity instanceof Entity){
//            $referenced = $entity->getReferenced();
//            $referencing = $entity->getReferencing();
            if($entity->isAttached()) /* UPDATE */ {
               $this->update($entity);
            } else /* INSERT */ {
                $returnPrimary = !empty($entity->getPrimaryValues());
                $id = $this->insert($entity, $returnPrimary);
                $entity->makeAlive($this->entityFactory, $this->mapper, $this->connection);
                $entity->attach($id);
//                dd($entity, $entity->isAttached());
            }
//            $this->persistBelongsToMany($referencing);
        } else {
            return false;
        }
        return true;
    }

    /**
     * TODO: IS NOT IMPLEMENTED YET
     * @param Entity[] $items
     */
    protected function persistBelongsToMany(array $items){
        $bulkInsert = [];
        foreach ($items as $item){
            $row = $item->toArray();
            $row['FOREGIN_KEY'] = 'FOREGIN_KEY';
            $bulkInsert[] = $row;
        }


    }

    public function insertBulk(array $rows){
        $rawRows = $this->extractRawRows($rows);
        $this->dibi->insert($this->getTableName(), $rawRows);
    }

    /**
     * @param $row
     * @param bool $retPrimary
     * @return bool|mixed
     * @throws Exception
     */
    public function insert($row, $retPrimary = false) {
        if ($row instanceof Entity) {
            $row = $row->toArray();
        }
        try {
//            dd($this->dibi->insert($this->getTableName(), $row)->test());
            $this->dibi->insert($this->getTableName(), $row)->execute();
            return $retPrimary ? $this->dibi->getInsertId() : null;
        } catch (\Dibi\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function update($data, $where = null) {
        if ($data instanceof Entity) {
            if (count($where) > 0) throw new Exception('Cannt use argument $where when argument $data is Entity;');
            $where = $data->getPrimaryValues();
            $data = $data->toArray();
        } else if (!is_array($where) || count($where) <= 0) {
            throw new Exception('Argument $where must be array with more items.');
        }

        try {
            $this->dibi->update($this->getTableName(), $data)->where($where)->execute();
        } catch (\Dibi\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        return true;
    }

    public function findById($id) {
        $primaryWhereConditions = null;
        if (!$id) return null;
        if (is_array($id) && ArrayUtils::isAssoc($id)) {
            $primaryWhereConditions = $id;
        } else {
            $primary = $this->mapper->getPrimaries($this->getEntityClass());
            if (is_array($id) && count($primary) !== count($id)) {
                throw new Exception('Defined primary keys are [' . implode(',', $primary) . '] you are trying to set keys [' . implode(',', $id) . ']. ');
            }
            $primaryWhereConditions = array_combine($primary, is_array($id) ? $id : [$id]);
        }

        $row = $this->dibi->select('*')->from($this->getTableName())->where($primaryWhereConditions)->fetch();
        return $this->createEntity($row ? $row->toArray() : []);
    }

    public function getSelectionFindAll(array $where = [], $orderBy = [], $limit = null, $offset = null) {
        $table = $this->mapper->getTableByRepositoryClass(get_called_class());
        $fluent = $this->dibi->select('*')->from($table);
        if ($where) $fluent->where($where);
        if ($orderBy) $fluent->orderBy($orderBy);
        if ($limit) $fluent->limit($limit);
        if ($offset) $fluent->offset($offset);

        return $fluent;
    }

    public function findAll(array $where = [], $orderBy = [], $limit = null, $offset = null) {
        $fluent = $this->getSelectionFindAll($where, $orderBy, $limit, $offset);
        return $this->createEntities($fluent->fetchAll());
    }

    public function findOne(array $where = [], $orderBy = []) {
        $table = $this->mapper->getTableByRepositoryClass(get_called_class());
        $fluent = $this->dibi->select('*')->from($table);
        if ($where) $fluent->where($where);
        if ($orderBy) $fluent->orderBy($orderBy);
        $row = $fluent->fetch();

        return $this->createEntity($row);
    }

    private function extractRawRows(array $rows){
        $rawRows = [];
        foreach ($rows as $row){
            if($row instanceof Entity) {
                $rawRows[] = $row->toArray();
            } else if(is_array($row)) {
                $rawRows[] = $row();
            } else {
                throw new Exception("Item `$row` isn't instance of Entity or array");
            }
        }
        return $rawRows;
    }


}