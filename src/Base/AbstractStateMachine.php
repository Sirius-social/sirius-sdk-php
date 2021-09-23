<?php


namespace Siruis\Base;


abstract class AbstractStateMachine
{
    public $time_to_live;
    public $is_aborted;
    public $coProtocols;
    protected $logger;

    public function __construct(int $time_to_live = 60, $logger = null)
    {
        $this->time_to_live = $time_to_live;
        $this->is_aborted = false;
        $this->coProtocols = [];
        $this->logger = $logger;
    }

    public function abort()
    {
        $this->is_aborted = true;
        foreach ($this->coProtocols as $co) {
            $co->abort();
        }
        unset($this->coProtocols);
    }

    public function log(array $kwargs)
    {
        if ($this->logger) {
            $kwargs['state_machine_id'] = spl_object_id($this);
//            $this->logger($kwargs);
        } else {
            return false;
        }
    }

    public function _register_for_aborting($co)
    {
        array_push($this->coProtocols, $co);
    }

    public function _unregister_for_aborting($co)
    {
        foreach ($this->coProtocols as $item) {
            if (spl_object_id($item) != spl_object_id($co)) {
                array_push($this->coProtocols, $item);
            }
        }
    }

    protected function __instances($resp, array $classes)
    {
        $instances = [];
        foreach ($classes as $class) {
            if ($resp instanceof $class) {
                array_push($instances, $class);
            }
        }
        return count($instances) == count($classes);
    }
}