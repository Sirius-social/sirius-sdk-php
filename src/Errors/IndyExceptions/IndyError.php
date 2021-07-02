<?php


namespace Siruis\Errors\IndyExceptions;

use Exception;
use Siruis\Helpers\ArrayHelper;
use Throwable;

class IndyError extends Exception
{
    public $error_code;
    public $message;
    public $indy_backtrace;

    public function __construct(
        int $error_code,
        array $error_details = null,
        $message = "",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
        if ($error_details) {
            $this->message = $error_details['message'];
            $this->indy_backtrace = ArrayHelper::getValueWithKeyFromArray('backtrace', $error_details);
        }
    }
}
