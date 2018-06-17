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

    public $entitySchema = [];


    public function __construct($argument,$cache = null) {
        parent::__construct($argument);
        $this->parseProperties();
    }

    public function parseProperties() {
        $annotations = AnnotationsParser::getAll($this);
        $properties = OrmAnotationPareser::parseOrmPropertiesTags($annotations['property']);
        foreach ($properties as $var => $property) {

            if (key_exists('column', $property)) {
                if ((is_array($property['column']) || $property['column'] instanceof \Countable) && count($property['column']) > 1) throw new IncorrectAnotationException("Annotation @column on property $property->name is there more than one time.");
                $column = $property['column'];
                if ($column === true) $column = $var;

                $this->entitySchema['vars'][$var] = null;
                if (key_exists('primary', $property)) {
                    $this->entitySchema['primary'][] = $column;
                }

                $this->entitySchema['columns'][$column] = $var;
                if(key_exists('hasMany', $property)){
                    $this->entitySchema['hasMany'][$column] = $property['hasMany'];
                }
                if(key_exists('hasOne', $property)){
                    $this->entitySchema['hasOne'][$column] = $property['hasOne'];
                }
            }
        }
    }

    public function getTableName() {
        return key_exists('tablename', $this->entitySchema) ? $this->entitySchema['tablename'] : $this->entitySchema['tablename'] = $this->trimNamespace($this->getName());
    }

    public function getColumnVariable(string $column) {
        if(key_exists('columns', $this->entitySchema) && array_key_exists($column, $this->entitySchema['columns']))
            return $this->entitySchema['columns'][$column];
        return null;
    }

    public function getColumns() {
        return key_exists('columns', $this->entitySchema) ? $this->entitySchema['columns'] : [];
    }

    public function getVariables() {
        return key_exists('vars', $this->entitySchema) ? $this->entitySchema['vars'] : [];
    }

    public function getPrimary() {
        return key_exists('primary', $this->entitySchema) ? $this->entitySchema['primary'] : null;
    }

    public function getHasMany(){
        return key_exists('hasMany', $this->entitySchema) ? $this->entitySchema['hasMany'] : null;
    }

    public function getHasOne(){
        return key_exists('hasOne', $this->entitySchema) ? $this->entitySchema['hasOne'] : null;
    }

    public function trimNamespace($class) {
        $r = explode('\\', $class);
        return end($r);
    }
}