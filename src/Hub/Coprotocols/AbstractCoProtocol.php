<?php


namespace Siruis\Hub\Coprotocols;


abstract class AbstractCoProtocol
{
    private $time_to_live, $is_aborted;
    protected $hub, $is_start, $transport;

    /**
     * AbstractCoProtocol constructor.
     * @param int|null $time_to_live
     */
    public function __construct(int $time_to_live = null)
    {
        $this->time_to_live = $time_to_live;
        $this->is_aborted = false;
        $this->hub = null;
        $this->is_start = false;
        $this->transport = null;
    }

    public function getTTL(): ?int
    {
        return $this->time_to_live;
    }

    public function getIsAborted(): bool
    {
        return $this->is_aborted;
    }

    /**
     * Abort coprotocol.
     *
     * @return void
     */
    public function abort(): void
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

    /**
     * Clean coprotocol.
     *
     * @return void
     */
    public function clean(): void
    {
        if ($this->is_start) {
            $this->transport->stop();
            $this->is_start = false;
        }
        $this->transport = null;
    }
}