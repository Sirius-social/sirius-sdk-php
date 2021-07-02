<?php


namespace Siruis\Errors\Exceptions;


use Throwable;

class StateMachineTerminatedWithError extends \Exception
{
    /**
     * @var string
     */
    public $problem_code;
    /**
     * @var string
     */
    public $explain;
    /**
     * @var bool
     */
    public $notify;

    public function __construct(string $problem_code, string $explain, bool $notify = true, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->problem_code = $problem_code;
        $this->explain = $explain;
        $this->notify = $notify;
    }

    public function __toString(): string
    {
        return 'problem_code: ' . $this->problem_code . '; explain: ' . $this->explain . '; notify: ' . $this->notify;
    }
}