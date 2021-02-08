<?php


namespace Siruis\RPC\Futures;


use DateTime;
use Exception;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusPromiseContextException;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Errors\Exceptions\SiriusValueEmpty;
use Siruis\Errors\IndyExceptions\ErrorCodeToException;
use Siruis\RPC\Tunnel\AddressedTunnel;
use SodiumException;

class Future
{
    const MSG_TYPE = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/future';

    /**
     * @var string
     */
    public $id;
    /**
     * @var null
     */
    public $value;
    /**
     * @var bool
     */
    public $read_ok;
    /**
     * @var AddressedTunnel
     */
    public $tunnel;
    /**
     * @var null
     */
    public $exception;
    /**
     * @var DateTime|null
     */
    public $expiration_time;

    /**
     * Future constructor.
     * @param AddressedTunnel $tunnel
     * @param DateTime|null $expiration_time
     */
    public function __construct(AddressedTunnel $tunnel, DateTime $expiration_time = null)
    {
        $this->id = uniqid();
        $this->value = null;
        $this->read_ok = false;
        $this->tunnel = $tunnel;
        $this->exception = null;
        $this->expiration_time = $expiration_time;
    }

    /**
     * @return array
     */
    public function getPromise()
    {
        return [
            'id' => $this->id,
            'channel_address' => $this->tunnel->address,
            'expiration_stamp' => $this->expiration_time ? $this->expiration_time->getTimestamp() : null
        ];
    }

    /**
     * @param int|null $timeout
     * @return bool
     * @throws SiriusCryptoError
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SodiumException
     * @throws Exception
     */
    public function wait(int $timeout = null)
    {
        if ($this->read_ok) {
            return true;
        }
        try {
            $now = new DateTime();
            if ($timeout == 0) {
                return false;
            }
            if ($this->expiration_time != null) {
                $expires_time = $this->expiration_time;
            } elseif ($timeout != null) {
                $expires_time = date("Y-m-d h:i:s", time() + $timeout);
            } else {
                $now->modify('+1 year');
                $expires_time = $now->format('Y-m-d h:i:s');
            }
            while (date('Y-m-d h:i:s') < $expires_time) {
                $timedelta = (new DateTime($expires_time))->diff(new DateTime());
                $timeout = max($timedelta->s, 0);
                $payload = $this->tunnel->receive($timeout);
                $payload = json_decode($payload->serialize(), true);
                if (
                    key_exists('@type', $payload) &&
                    $payload['@type'] == self::MSG_TYPE &&
                    key_exists('~thread', $payload) &&
                    $payload['~thread']['thid'] == $this->id
                ) {
                    if (key_exists('exception', $payload) && $payload['exception']) {
                        $this->exception = $payload['exception'];
                    } else {
                        $value = $payload['value'];
                        if ($payload['is_tuple']) {
                            $this->value = (array)$value;
                        } elseif ($payload['is_bytes']) {
                            $this->value = Encryption::b64_to_bytes($value);
                        } else {
                            $this->value = $value;
                        }
                    }
                    $this->read_ok = true;
                    return true;
                }
                return false;
            }
        } catch (SiriusTimeoutIO $exception) {
            return false;
        }
    }

    /**
     * @return null
     * @throws SiriusPendingOperation
     */
    public function getValue()
    {
        if (!$this->read_ok) {
            throw new SiriusPendingOperation();
        }
        return $this->value;
    }

    /**
     * @return bool
     * @throws SiriusPendingOperation
     */
    public function hasException() : bool
    {
        if (!$this->read_ok) {
            throw new SiriusPendingOperation();
        }
        return $this->exception != null;
    }

    /**
     * @return mixed|SiriusPromiseContextException|null
     * @throws SiriusPendingOperation
     */
    public function getException()
    {
        if ($this->hasException()) {
            if (key_exists('indy', $this->exception) && $this->exception['indy']) {
                $indy_exc = $this->exception['indy'];
                $exc_class = ErrorCodeToException::parse($indy_exc['error_code']);
                return new $exc_class(
                    $indy_exc['error_code'],
                    [
                        'message' => $indy_exc['message'],
                        'indy_backtrace' => null
                    ]);
            } else {
                return new SiriusPromiseContextException(
                    $this->exception['class_name'],
                    $this->exception['printable']
                );
            }
        } else {
            return null;
        }
    }

    /**
     * @throws SiriusPendingOperation
     * @throws SiriusPromiseContextException
     * @throws SiriusValueEmpty
     */
    public function throwException()
    {
        if ($this->hasException()) {
            throw $this->getException();
        } else {
            throw new SiriusValueEmpty();
        }
    }
}