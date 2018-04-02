<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/6/18
 * Time: 11:38 PM
 */
namespace Nutillea\EntityMapper;

use Nutillea\EntityMapper\Exception\Exception;
use Nutillea\EntityMapper\Factory\EntityFactory;
use Nutillea\Utils\ArrayUtils;

class Repository
{
    use \Nette\SmartObject;

    /** @var \Dibi\Connection */
    public $dibi;

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
    public function __construct(\Dibi\Connection $dibi, Mapper $mapper, EntityFactory $entityFactory){
        $this->dibi = $dibi;
        $this->mapper = $mapper;
        $this->entityFactory = $entityFactory;
    }


    public function getEntityClass(){
        if(!$this->entityClass)
            $this->entityClass = $this->mapper->getEntityByRepositoryClass(get_called_class());
        return $this->entityClass;
    }

    public function getTableName(){
        if(!$this->tableName) {
            $this->tableName = $this->mapper->getTableByRepositoryClass(get_called_class());
        }
        return $this->tableName;
    }

    public function createEntities(array $data){
        $self = $this;
        return array_map(function($item) use ($self) { return $this->entityFactory->create($this->getEntityClass(), $item->toArray()); }, $data);
    }

    public function delete($primary = []){
        return true;
    }

    /**
     * @param $data
     * @param bool $retPrimary
     * @return bool|mixed
     * @throws Exception
     */
    public function insert($data, $retPrimary = false){
        if ($data instanceof Entity) {
            $data = $data->toArray();
        }
        try {
            $this->dibi->insert($this->getTableName(), $data)->execute();
            return $retPrimary ? $this->dibi->getInsertId() : true;
        } catch (\Dibi\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function update($data){
        if ($data instanceof Entity) {
            $data = $data->toArray();
        }
        try {
            $this->dibi->update($this->getTableName(), $data);
//                ->where(...)
//                ->execute();
            return  true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

    }

    public function findById($id){
        $primaryWhereConditions = null;
        if(is_array($id) && ArrayUtils::isAssoc($id)){
            $primaryWhereConditions = $id;
        } else {
            $primary = $this->mapper->getPrimary($this->getEntityClass());
            if(is_array($id) && count($primary) !== count($id)){
                throw new Exception('Defined primary keys are ['.implode(',', $primary).'] you are trying to set keys ['.implode(',', $id).']. ');
            }
            $primaryWhereConditions = array_combine($primary, is_array($id) ? $id : [$id]);
        }

        $row = $this->dibi->select('*')
            ->from($this->getTableName())
            ->where($primaryWhereConditions)
            ->fetch();

        return $this->entityFactory->create($this->getEntityClass(), $row->toArray());
    }

    public function findAll(array $where = [], $orderBy=[], $limit=null, $offset=null){

        $table = $this->mapper->getTableByRepositoryClass( get_called_class());
        $fluent = $this->dibi->select('*')
            ->from($table);
        if($where) $fluent->where($where);
        if($orderBy) $fluent->orderBy($orderBy);
        if($limit) $fluent->limit($limit);
        if($offset) $fluent->offset($offset);
            $rows =$fluent->fetchAll();

        return $this->createEntities($rows);
    }

}