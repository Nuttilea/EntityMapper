<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 3/14/18
 * Time: 9:00 AM
 */

namespace Nutillea\EntityMapper\Generator;


class AnnotationGenerator {
    private $description;
    private $annotations = [];
    private $annotationPrefix = '@';

    /**
     * @param mixed $description
     * @return AnnotationGenerator
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    public function addAnnotation($anotation, $value=null, $description=null){
        $this->annotations[$anotation][] = [
            'value' => $value,
            'description' => $description
        ];
    }

    public function __toString(){
        $res = '';
        foreach ($this->annotations as $key => $annotations){
            foreach ( $annotations as $annotation) {
                $a = $this->annotationPrefix . $key;
                if($annotation['value']){
                    //TODO: associative array vs array
                    $value = $annotation['value'];
                    if(is_array($value)){
                        if((array_values($value) !== $value)) {
                            $value = array_map(function($value, $key){ return $key.'='.$value; } ,array_values($value), array_keys($value));
                        }
                        $a .= '('. implode(',', $value) .')';
                    } else {
                        $a .= ' '.$value;
                    }
                }
                if ($annotation['description']) $a .= " " . $annotation['description'];
                $res .= "\n " . $a;
            }
        }
        return $res;
    }
}