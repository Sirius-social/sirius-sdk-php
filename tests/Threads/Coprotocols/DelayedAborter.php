<?php


namespace Siruis\Tests\Threads\Coprotocols;


use Siruis\Hub\Coprotocols\AbstractCoProtocol;
use Threaded;

class DelayedAborter extends Threaded
{
    /**
     * @var AbstractCoProtocol
     */
    private $co;

    public function __construct(AbstractCoProtocol $co)
    {
        $this->co = $co;
    }

    public function work()
    {
        $this->co->abort();
    }
}