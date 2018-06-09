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

    public $entityScheme = [];

    public function __construct($argument, $cache = null) {
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

                $this->entityScheme['columns'][$column] = $var;
                $this->entityScheme['vars'][$var] = null;
                if (key_exists('primary', $property)) {
                    $this->entityScheme['primary'][] = $column;
                }
            }
        }
    }

    public function getTableName() {
        return array_key_exists('tablename', $this->entityScheme) ? $this->entityScheme['tablename'] : $this->entityScheme['tablename'] = $this->trimNamespace($this->getName());
    }

    public function getColumnVariable(string $column) {
        return array_key_exists('columns', $this->entityScheme) && array_key_exists($column, $this->entityScheme['columns']) ? $this->entityScheme['columns'][$column] : null;
    }

    public function getColumns() {
        return array_key_exists('columns', $this->entityScheme) ? $this->entityScheme['columns'] : [];
    }

    public function getVariables() {
        return array_key_exists('vars', $this->entityScheme) ? $this->entityScheme['vars'] : [];
    }

    public function getPrimary() {
        return array_key_exists('primary', $this->entityScheme) ? $this->entityScheme['primary'] : null;
    }

    public function trimNamespace($class) {
        $r = explode('\\', $class);
        return end($r);
    }
}