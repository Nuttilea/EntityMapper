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

class Entity
{

    use SmartObject;

    /** @var Mapper */
    public $mapper;

    private $data = [];

    /** @var ReflectionEntity */
    public $reflection;

    /**
     * Repository constructor.
     */
    public function __construct(Mapper $mapper = null, array $data = []){
        if($mapper==null) $mapper=new Mapper();
        $this->mapper = $mapper;

        $this->data = $this->getReflection()->getVariables();
        if(!empty($data)) $this->setData($data);
    }

    public function __call($name, $arguments)
    {
        die($name);
    }

    public function __get($name)
    {
        if(array_key_exists($name, $this->data)){
            return $this->data[$name];
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function getReflection(){
        if(!$this->reflection) {
            $this->reflection = $this->mapper->getReflectionEntity(get_called_class());
        }
        return $this->reflection;
    }

    public function getPrimaryValues(){
        $primaries = $this->reflection->getPrimary();

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

    public function getTableName(){
        return $this->reflection->getTableName();
    }

    public function setData(array $data){
        foreach ($data as $column => $value){
            $var = $this->getReflection()->getColumnVariable($column);
            if(!$var) throw new \Exception("TODO ...." . $var);
            $this->{$var} = $value;
        }
    }

    public function toArray(){
        return $this->data;
    }

    //Pokud udelam tohle, tak
    public function isAttached(){

    }
}

