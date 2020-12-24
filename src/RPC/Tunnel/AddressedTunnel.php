<?php


namespace Siruis\RPC\Tunnel;


use Exception;
use Siruis\Base\ReadOnlyChannel;
use Siruis\Base\WriteOnlyChannel;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Messaging\Message;
use SodiumException;
use TypeError;

class AddressedTunnel
{
    const ENC = 'utf-8';
    public $address;
    public $input;
    public $output;
    public $p2p;
    public $context;

    /**
     * AddressedTunnel constructor.
     *
     * @param string $address communication address of transport environment on server-side
     * @param ReadOnlyChannel $input channel of input stream
     * @param WriteOnlyChannel $output channel of output stream
     * @param P2PConnection $p2p pairwise connection that configured and prepared outside
     */
    public function __construct(
        string $address,
        ReadOnlyChannel $input,
        WriteOnlyChannel $output,
        P2PConnection $p2p
    )
    {
        $this->address = $address;
        $this->input = $input;
        $this->output = $output;
        $this->p2p = $p2p;
        $this->context = new Context();
    }

    public function address()
    {
        return $this->address;
    }

    public function context()
    {
        return $this->context;
    }

    /**
     * Read message.
     *
     * Tunnel allows to receive non-encrypted messages, high-level logic may control message encryption flag
     * via context.encrypted field
     *
     * @param int|null $timeout timeout in seconds
     *
     * @return Message received packet
     *
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     */
    public function receive(int $timeout = null) : Message
    {
        $payload = $this->input->read($timeout);
//        if (!is_string($payload[0]) || !is_array($payload[0])) {
//            throw new TypeError('Expected bytes or dict, got ' . gettype($payload));
//        }
        if (is_string($payload[0])) {
            try {
                $payload = json_decode($payload[0]);
            } catch (Exception $e) {
                throw new SiriusInvalidPayloadStructure('Invalid packed message ' . $e);
            }
        }
        if (key_exists('protected', $payload)) {
            $unpacked = $this->p2p->unpack(json_encode($payload));
            $this->context->encrypted = true;
            return new Message($unpacked);
        } else {
            $this->context->encrypted = false;
            return new Message($payload);
        }
    }

    /**
     * Write message
     *
     * @param Message $message message to send
     * @param bool $encrypt do encryption
     *
     * @return bool operation success
     *
     * @throws SiriusCryptoError
     * @throws SodiumException
     */
    public function post(Message $message, bool $encrypt = true) : bool
    {
        if ($encrypt) {
            $payload = $this->p2p->pack((array)$message);
        } else {
            $payload = mb_convert_encoding($message->serialize(), self::ENC);
        }
        return $this->output->write($payload);
    }
}