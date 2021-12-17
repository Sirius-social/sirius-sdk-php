<?php


namespace Siruis\RPC\Tunnel;

use Exception;
use Siruis\Base\ReadOnlyChannel;
use Siruis\Base\WriteOnlyChannel;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Messaging\Message;
use TypeError;

class AddressedTunnel
{
    public const ENC = 'utf-8';
    /**
     * @var string
     */
    public $address;
    /**
     * @var \Siruis\Base\ReadOnlyChannel
     */
    protected $input;
    /**
     * @var \Siruis\Base\WriteOnlyChannel
     */
    protected $output;
    /**
     * @var \Siruis\Encryption\P2PConnection
     */
    protected $p2p;
    /**
     * @var \Siruis\RPC\Tunnel\Context
     */
    public $context;

    /**
     * AddressedTunnel constructor.
     *
     * @param string $address communication address of transport environment on server-side
     * @param \Siruis\Base\ReadOnlyChannel $input channel of input stream
     * @param \Siruis\Base\WriteOnlyChannel $output channel of output stream
     * @param \Siruis\Encryption\P2PConnection $p2p pairwise connection that configured and prepared outside
     */
    public function __construct(
        string $address,
        ReadOnlyChannel $input,
        WriteOnlyChannel $output,
        P2PConnection $p2p
    ) {
        $this->address = $address;
        $this->input = $input;
        $this->output = $output;
        $this->p2p = $p2p;
        $this->context = new Context();
    }

    /**
     * Read message.
     *
     * Tunnel allows receiving non-encrypted messages, high-level logic may control message encryption flag via context.
     * Encrypted field
     *
     * @param int|null $timeout timeout in seconds
     * @return \Siruis\Messaging\Message received packet
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \JsonException
     */
    public function receive(int $timeout = null) : Message
    {
        $payload = $this->input->read($timeout);
        if (!is_string($payload) && !is_array($payload)) {
            throw new TypeError('Expected bytes or dict, got ' . gettype($payload));
        }
        if (is_array($payload) && count($payload) === 1) {
            $payload = $payload[0];
        }
        if (is_string($payload)) {
            try {
                $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new SiriusInvalidPayloadStructure('Invalid packed message ' . $e);
            }
        }
        if (array_key_exists('protected', $payload)) {
            $unpacked = $this->p2p->unpack($payload);
            $this->context->encrypted = true;
            return new Message(json_decode($unpacked, true, 512, JSON_THROW_ON_ERROR));
        }

        $this->context->encrypted = false;
        return new Message($payload);
    }

    /**
     * Write message
     *
     * @param \Siruis\Messaging\Message $message message to send
     * @param bool $encrypt do encryption
     * @return void
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \SodiumException
     */
    public function post(Message $message, bool $encrypt = true): void
    {
        if ($encrypt) {
            $payload = $this->p2p->pack($message->payload);
        } else {
            $payload = mb_convert_encoding($message->serialize(), self::ENC);
        }
        $this->output->write($payload);
    }
}
