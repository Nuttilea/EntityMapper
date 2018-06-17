<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 6/16/18
 * Time: 3:33 PM
 */

namespace Nuttilea\EntityMapper;


class Property {
    private $name;
    private $value;
    private $relation;

    /**
     * Property constructor.
     * @param $name
     * @param $value
     * @param $relation
     */
    public function __construct($name, $value, $relation) {
        $this->name = $name;
        $this->value = $value;
        $this->relation = $relation;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getRelation() {
        return $this->relation;
    }


}