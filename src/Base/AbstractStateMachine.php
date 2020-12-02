<?php


namespace Siruis\Base;


abstract class AbstractStateMachine
{
    public $time_to_live;
    public $is_aborted;
    public $coProtocols;

    public function __construct(int $time_to_live = 60, $logger = null)
    {
        $this->time_to_live = $time_to_live;
        $this->is_aborted = false;
        $this->coProtocols = [];
    }

    public function timeToLive() : int
    {
        return $this->time_to_live;
    }

    public function isAborted() : bool
    {
        return $this->is_aborted;
    }
}