<?php
/**
 * Created by PhpStorm.
 * User: tonda
 * Date: 4/1/18
 * Time: 7:08 PM
 */

namespace Nutillea\EntityMapper\Exception;


use Throwable;

class Exception extends \Exception {
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}