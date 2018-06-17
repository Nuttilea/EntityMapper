<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/17/18
 * Time: 8:22 PM
 */

namespace Nuttilea\EntityMapper;

class Property {

    public $column;

    public $variable;

    public $relationship;

    public $primary;

    public function __construct($variable) {
        $this->variable = $variable;
    }

    public function setPrimary(){
        $this->primary;
    }

    public function getPrimary(){
        return $this->primary;
    }

    /**
     * @return mixed
     */
    public function getColumn() {
        return $this->column;
    }

    /**
     * @param mixed $column
     */
    public function setColumn($column) {
        $this->column = $column;
    }

    /**
     * @return mixed
     */
    public function getVariable() {
        return $this->variable;
    }

    /**
     * @param mixed $variable
     */
    public function setVariable($variable) {
        $this->variable = $variable;
    }

    /**
     * @return mixed
     */
    public function getRelationship() {
        return $this->relationship;
    }

    /**
     * @param mixed $relationship
     */
    public function setRelationship($relationship) {
        $this->relationship = $relationship;
    }





}