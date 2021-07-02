<?php


namespace Siruis\Agent\Connections;


use ArrayObject;

class RoutingBatch extends ArrayObject
{
    public $recipientVerkeys;
    public $endpointAddress;
    public $senderVerkey;
    public $routingKeys;

    /**
     * RoutingBatch constructor.
     * @param array|string $theirVk
     * @param string $endpoint
     * @param string|null $myVk
     * @param array|null $routingKeys
     * @param array $array
     * @param int $flags
     * @param string $iteratorClass
     */
    public function __construct($theirVk,
                                string $endpoint,
                                string $myVk = null,
                                array $routingKeys = null,
                                $array = array(),
                                $flags = 0,
                                $iteratorClass = "ArrayIterator")
    {
        parent::__construct($array, $flags, $iteratorClass);
        if (is_string($theirVk)) {
            $this->recipientVerkeys = [$theirVk];
        } else {
            $this->recipientVerkeys = $theirVk;
        }
        $this->endpointAddress = $endpoint;
        $this->senderVerkey = $myVk;
        $this->routingKeys = $routingKeys;
    }
}