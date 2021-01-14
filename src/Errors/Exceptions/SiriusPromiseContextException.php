<?php

namespace Siruis\Errors\Exceptions;

use Exception;
use Throwable;

class SiriusPromiseContextException extends Exception
{
    public $class_name;

    public $printable;

    public function __construct($class_name, $printable, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->class_name = $class_name;

        $this->printable = $printable;

        parent::__construct($message, $code, $previous);
    }
}