<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 7/22/18
 * Time: 12:33 AM
 */

namespace Nuttilea\EntityMapper;

class Utils {

    public static function getParentMethod(){
        $e = new \Exception();
        $trace = $e->getTrace();
        if(count($trace) > 2){
            return $trace[2]['function'];
        }
        return null;
    }

}