<?php


namespace Siruis\Errors\Exceptions;


use Exception;
use Throwable;

class SiriusFieldTypeError extends Exception
{
    public function __construct($v_name, $v_value, $v_exp_t, $message = "", $code = 0, Throwable $previous = null)
    {
        $type = gettype($v_value);
        $message .= "variable $v_name, type $type excepted: $v_exp_t";

        parent::__construct($message, $code, $previous);
    }
}