<?php
namespace Test\classes;

use Nuttilea\EntityMapper\Entity;


/**
 * Class TagsEntity
 *
 * @\nuttilea\Entity tags
 * @\nuttilea\Repository TagsRepository
 *
 * @property int $id orm:column(ID) orm:primary
 * @property int $tag orm:column
 * @property object[] $ints orm:column(ID)
 */
class Tags extends Entity
{

}