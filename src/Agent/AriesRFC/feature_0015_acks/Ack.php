<?php


namespace Siruis\Agent\AriesRFC\feature_0015_acks;


use Siruis\Agent\Base\AriesProtocolMessage;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Message;
use Siruis\Messaging\Validators;

class Ack extends AriesProtocolMessage
{
    public $PROTOCOL = 'notification';
    public $NAME =  'ack';

    public const PROTOCOL = 'notification';
    public const NAME = 'ack';

    /**
     * @var string|null
     */
    public $thread_id;

    /**
     * @var Status|string|null
     */
    public $status;

    /**
     * @var array
     */
    public $payload;

    /**
     * Ack constructor.
     * @param array $payload
     * @param string|null $id_
     * @param string|null $version
     * @param string|null $doc_uri
     * @param string|null $thread_id
     * @param Status|string|null $status
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public function __construct(
        array $payload,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null,
        string $thread_id = null,
        $status = null
    )
    {
        parent::__construct($payload, $id_, $version, $doc_uri);
        if (key_exists('status', $payload)) {
            $status = $payload['status'];
        }
        if ($status != null) {
            if ($status instanceof Status) {
                $this->status = $status->getValue();
            } else {
                $this->status = $status;
            }
        }
        if ($thread_id) {
            $thread = $payload[self::THREAD_DECORATOR];
            $thread['thid'] = $thread_id;
            $payload[self::THREAD_DECORATOR] = $thread;
        }
        $this->payload = $payload;
        Message::registerMessageClass(Ack::class, $this->PROTOCOL, $this->NAME);
    }

    public function validate()
    {
        parent::validate();
        $validator = new Validators();
        $validator->check_for_attributes($this->payload, [self::THREAD_DECORATOR]);
        $validator->check_for_attributes($this->payload[self::THREAD_DECORATOR], ['thid']);
    }

    public function getThreadId()
    {
        return $this->payload[self::THREAD_DECORATOR]['thid'];
    }

    public function getStatus()
    {
        if (!$this->status || $this->status == Status::OK) {
            return Status::OK;
        } elseif ($this->status == Status::PENDING) {
            return Status::PENDING;
        } elseif ($this->status == Status::FAIL) {
            return Status::FAIL;
        }
        return null;
    }
}