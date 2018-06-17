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
use Nuttilea\EntityMapper\Factory\RepositoryFactory;

class Entity
{

    use SmartObject;

    /** @var Row */
    private $row;

    /** @var []ReflectionEntity */
    protected static $reflections;

    /** @var ReflectionEntity */
    private $currentReflection;

    /** @var boolean */
    private $attached = false;

    /**
     * Repository constructor.
     */
    public function __construct($dataset = null){
        //INIT DATASET
        if($dataset instanceof Row){
            $this->row = $dataset;
        } else {
            $this->row = Result::createDetachedInstance()->getRow();
            //SETUP DEFAULT VALUES
            //            foreach ($this->getCurrentReflection()->getColumns() as $column) {
            //                $this->__set($column);
            //            }
        }
        if(!empty($dataset)) $this->assign($dataset);
    }

    public function __call($name, $arguments)
    {
        die($name);
    }

    public function __get($name)
    {
        $hasOne = $this->getCurrentReflection()->getHasOne();
        if($hasOne && key_exists($name, $hasOne)){
//            $this->mapper->getRepositoryByEntityClass(get_called_class());
        }
        dd($this->row->action);
        return $this->row[$name];

    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
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

    public function makeAlive(){
        $this->attached = true;
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
                $var = $reflection->getColumnVariable($column);
                if(!$var) throw new \Exception("Column `$column` is not defined!");
                $this->row[$var] = $value;
            }
        }
    }

    public function toArray(){
        return $this->row->toArray();
    }
}

