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
     * @return array|string
     * @throws SiriusTimeoutIO
     */
    public function read($timeout = null)
    {
        $ret = $this->internal_reading();
        if (is_string($ret) || is_array($ret)) {
            return $ret;
        }

        throw new SiriusTimeoutIO();
    }

    private function internal_reading(): array
    {
        return $this->queue->toArray();
    }

    /**
     * Write message packet
     *
     * @param string $data
     * @return bool
     */
    public function write(string $data): bool
    {
        $this->queue->push($data);
        return true;
    }
}