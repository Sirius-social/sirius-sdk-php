<?php


namespace Siruis\Agent\Base;


use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Messaging\Message;

class RegisterMessage
{
    private $cls;
    /**
     * RegisterMessage constructor.
     * @param $cls
     * @throws SiriusInvalidMessageClass
     */
    public function __construct($cls)
    {
        if (is_subclass_of($cls, 'Siruis\Agent\Base\AriesProtocolMessage')) {
            Message::registerMessageClass($cls, $cls::PROTOCOL, $cls::NAME);
        }
        $this->cls = $cls;
    }
}