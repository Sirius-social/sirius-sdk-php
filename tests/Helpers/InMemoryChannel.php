<?php


namespace Siruis\Tests\Helpers;


use Ds\Deque;
use Siruis\Base\ReadOnlyChannel;
use Siruis\Base\WriteOnlyChannel;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;

class InMemoryChannel implements WriteOnlyChannel, ReadOnlyChannel
{
    protected $queue;

    public function __construct()
    {
        $this->queue = new Deque();
    }

    /**
     * Read message packet
     *
     * @param int|null $timeout
     * @return mixed
     * @throws SiriusTimeoutIO
     */
    public function read($timeout = null)
    {
        $ret = null;
        $ret = $this->internal_reading();
        if (is_string($ret) || is_array($ret)) {
            return $ret;
        } else {
            throw new SiriusTimeoutIO();
        }
    }

    private function internal_reading()
    {
        return $this->queue->toArray();
    }

    /**
     * Write message packet
     *
     * @param string $data
     * @return mixed
     */
    public function write(string $data)
    {
        $this->queue->push($data);
        return true;
    }
}