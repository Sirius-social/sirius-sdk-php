<?php


namespace Siruis\Errors\Exceptions;


use Exception;
use Throwable;

class SiriusFieldValueError extends Exception
{
    public function __construct($v_name, $v_value, $v_exp_t, $message = "", $code = 0, Throwable $previous = null)
    {
        $message .= "variable $v_name, value $v_value excepted: $v_exp_t";

        parent::__construct($message, $code, $previous);
    }
}