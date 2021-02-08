<?php


namespace Siruis\Hub\Coprotocols;


abstract class AbstractCoProtocol
{
    public $time_to_live;
    public $is_aborted;
    public $hub;
    public $is_start;
    public $transport;

    public function __construct(int $time_to_live = null)
    {
        $this->time_to_live = $time_to_live;
        $this->is_aborted = false;
        $this->hub = null;
        $this->is_start = false;
        $this->transport = null;
    }

    public function abort()
    {
        if ($this->hub) {
            $this->hub->run_soon($this->clean());
            if (!$this->is_aborted) {
                $this->is_aborted = true;
                $this->hub->abort();
                $this->hub = null;
            }
        }
    }

    public function clean()
    {
        if ($this->is_start) {
            $this->transport->stop();
            $this->is_start = false;
        }
        $this->transport = null;
    }
}