<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/6/18
 * Time: 11:37 PM
 */
namespace Nutillea\EntityMapper;

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
    public function __construct(Mapper $mapper, array $data = []){
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
            return $this->{$name};
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

