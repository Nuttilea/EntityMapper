<?php
/**
 * Created by PhpStorm.
 * User: Antonin Sajboch
 * Date: 3/5/18
 * Time: 1:57 PM
 */

namespace Nutillea\EntityMapper;


use Nette\Utils\Strings;

class OrmAnotationPareser
{

    const RE_STRING = '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"';
    const RE_IDENTIFIER = '[_a-zA-Z\x7F-\xFF][_a-zA-Z0-9\x7F-\xFF-\\\]*';
    const RE_SNAME = 'orm:';

    public static function parseOrmPropertiesTags($properties){
        $res = [];
        static $tokens = ['true' => true, 'false' => false, 'null' => null, '' => true];

        $regexp_orm ='~((?<='.self::RE_SNAME.')'.self::RE_IDENTIFIER.') [ \t]* (\((?> '.self::RE_STRING.' | [^\'")@]+)+\) | (?>(?!'.self::RE_SNAME.'|  [\n\r]).)* |  )~mx';
        foreach ($properties as $prop){
            $match = Strings::match($prop, '~^(\w*(?>\s)*)?([$]\w*)?~mx');

            list(,$type,$variable) = $match;
            if($match && $variable )
            {
                $variable = substr($variable, 1);
                $res[$variable]['type'] = trim($type);

                foreach (Strings::matchAll($prop, $regexp_orm) as $m){
                    list(,$identifier, $value) = $m;
                    if (substr($value, 0, 1) === '(') {
                        $items = [];
                        $key = '';
                        $val = true;
                        $value[0] = ',';
                        while($v = Strings::match(
                            $value,
                            '#\s*,\s*(?>(' . self::RE_IDENTIFIER . ')\s*=\s*)?(' . self::RE_STRING . '|[^\'"),\s][^\'"),]*)#A')){
                            $value = substr($value, strlen($v[0]));
                            list(, $key, $val) = $v;
                            $val = rtrim($val);
                            if ($val[0] === "'" || $val[0] === '"') {
                                $val = substr($val, 1, -1);

                            } elseif (is_numeric($val)) {
                                $val = 1 * $val;

                            } else {
                                $lval = strtolower($val);
                                $val = array_key_exists($lval, $tokens) ? $tokens[$lval] : $val;
                            }

                            if ($key === '') {
                                $items[] = $val;

                            } else {
                                $items[$key] = $val;
                            }
                        }

                        $value = count($items) < 2 && $key === '' ? $val : $items;
                    } else {
                        $value = trim($value);
                        if (is_numeric($value)) {
                            $value = 1 * $value;

                        } else {
                            $lval = strtolower($value);
                            $value = array_key_exists($lval, $tokens) ? $tokens[$lval] : $value;
                        }
                    }
                    $res[$variable][$identifier] =  $value;
                }
            }
        }
        return $res;
    }
}