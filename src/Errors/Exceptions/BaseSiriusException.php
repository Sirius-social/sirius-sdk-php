<?php


namespace Siruis\Errors\Exceptions;


use Exception;
use Throwable;

class BaseSiriusException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
    }

    public function _prefix_msg($msg, $prefix = null)
    {
        return $prefix ? $prefix . $msg : '{}:'.$msg;
    }
}