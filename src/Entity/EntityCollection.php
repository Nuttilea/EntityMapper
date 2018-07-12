<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 7/10/18
 * Time: 6:31 PM
 */

namespace Nuttilea\EntityMapper;


use Nuttilea\EntityMapper\Exception\Exception;

class EntityCollection implements \ArrayAccess {

    /**
     * @var Entity[]
     */
    protected $entities = [];

    public function __construct(array $rows) {
        foreach ($rows as $row) {
            if($row instanceof Entity || is_array($row)) {
                $this->addEntity($row);
            } else {
                throw new Exception("Item `$row` isn't instance of Entity or array");
            }
        }
    }
    public static function createEntityCollection(array $data){
        return new self($data);
    }

    public function addEntity($entity){
        if($entity instanceof Entity ){
            $this->entities[spl_object_hash($entity)] = $entity->toArray();
        } elseif(is_array($entity)){
            $this->entities[spl_object_hash($entity)] = $entity;
        } else {
            throw new Exception("Item `$entity` isn't instance of Entity or array");
        }
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return isset($this->entities[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->entities[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $this->entities[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        unset($this->entities[$offset]);
    }
}