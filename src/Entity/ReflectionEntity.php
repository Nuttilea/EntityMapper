<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 2/11/18
 * Time: 3:27 PM
 */

namespace Nuttilea\EntityMapper;

use Nette\Reflection\AnnotationsParser;
use Nuttilea\EntityMapper\Exception\IncorrectAnotationException;

class ReflectionEntity extends \ReflectionClass {

    public $columns = [];
    public $props = [];
    public $primary = [];

    public function __construct($argument,$cache = null) {
        parent::__construct($argument);
        $this->parseProperties();
    }

    public function parseProperties() {
        $annotations = AnnotationsParser::getAll($this);
        $properties = OrmAnotationPareser::parseOrmPropertiesTags($annotations['property']);
        foreach ($properties as $var => $property) {
            $prop = new Property($var);
            if (key_exists('column', $property)) {
                if ((is_array($property['column']) || $property['column'] instanceof \Countable) && count($property['column']) > 1) throw new IncorrectAnotationException("Annotation @column on property $property->name is there more than one time.");
                $column = $property['column'];
                if ($column === true) $column = $var;
                $prop->setColumn($column);

                if (key_exists('primary', $property)) {
                    $prop->setPrimary();
                    $this->primary[] = $prop;
                }

                if(key_exists('hasMany', $property)){
                    list($targetTable, $targetTableColumn) = explode(':', $property['hasMany']) + [null, null];
                    $prop->setRelationship(new HasMany($targetTable, $targetTableColumn));
                }

                if(key_exists('hasOne', $property)){
                    list($table, $id) = explode(':', $property['hasOne']) + [null, null];
                    $prop->setRelationship(new HasOne($table, $id));
                }
                $this->columns[$column] = $prop;
            }
            if(key_exists('belongsToMany', $property)){
                list($targetTable, $targetTableColumn) = explode(':', $property['belongsToMany']) + [null, null];
                $prop->setRelationship(new BelongsToMany($targetTable, $targetTableColumn));
            }

            if(key_exists('belongsToOne', $property)){
                list($targetTable, $targetTableColumn) = explode(':', $property['belongsToOne']) + [null, null];
                $prop->setRelationship(new BelongsToOne($targetTable, $targetTableColumn));
            }
            $this->props[$var] = $prop;
        }

    }

    public function getTableName() {
        return key_exists('tablename', $this->entitySchema) ? $this->entitySchema['tablename'] : $this->entitySchema['tablename'] = $this->trimNamespace($this->getName());
    }

    /**
     * @param $name
     * @return Property
     */
    public function getProp($name) {
        return $this->props[$name];
    }

    /**
     * @param $column
     * @return Property
     */
    public function getPropByColumn($column){
        return $this->columns[$column];
    }

    public function getPrimaries(){
        return array_map(function ($prop){return $prop->getColumn();}, $this->primary);
    }

    public function getPrimary(){
        $p = $this->getPrimaries();
        return array_shift($p);
    }

    public function trimNamespace($class) {
        $r = explode('\\', $class);
        return end($r);
    }
}