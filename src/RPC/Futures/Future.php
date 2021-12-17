<?php


namespace Siruis\RPC\Futures;


use DateTime;
use DateTimeZone;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusPendingOperation;
use Siruis\Errors\Exceptions\SiriusPromiseContextException;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Errors\Exceptions\SiriusValueEmpty;
use Siruis\Errors\IndyExceptions\ErrorCodeToException;
use Siruis\RPC\Tunnel\AddressedTunnel;

/**
 * Futures and Promises pattern.
 * (http://dist-prog-book.com/chapter/2/futures.html)
 *
 *
 * Server point has internal communication schemas and communication addresses for
 * Aries super-protocol/sub-protocol behaviour
 * (https://github.com/hyperledger/aries-rfcs/tree/master/concepts/0003-protocols).
 *
 * Future hide communication addresses specifics of server-side service (cloud agent) and pairwise configuration
 * of communication between sdk-side and agent-side logic, allowing to take attention on
 * response awaiting routines.
 */
class Future
{
    public const MSG_TYPE = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/future';

    /**
     * @var string
     */
    protected $id;
    /**
     * @var null
     */
    protected $value;
    /**
     * @var bool
     */
    protected $read_ok;
    /**
     * @var \Siruis\RPC\Tunnel\AddressedTunnel
     */
    protected $tunnel;
    /**
     * @var null
     */
    protected $exception;
    /**
     * @var \DateTime|null
     */
    public $expiration_time;

    /**
     * Future constructor.
     *
     * @param \Siruis\RPC\Tunnel\AddressedTunnel $tunnel communication tunnel for server-side cloud agent
     * @param \DateTime|null $expiration_time time of response expiration
     */
    public function __construct(AddressedTunnel $tunnel, DateTime $expiration_time = null)
    {
        $this->id = uniqid('', true);
        $this->value = null;
        $this->read_ok = false;
        $this->tunnel = $tunnel;
        $this->exception = null;
        $this->expiration_time = $expiration_time;
    }

    /**
     * Promise info builder
     *
     * @return array serialized promise dump
     */
    public function getPromise(): array
    {
        return [
            'id' => $this->id,
            'channel_address' => $this->tunnel->address,
            'expiration_stamp' => $this->expiration_time ? $this->expiration_time->getTimestamp() : null
        ];
    }

    /**
     * Wait for response
     *
     * @param int|null $timeout waiting timeout in seconds
     * @return bool True/False
     * @throws \Siruis\Errors\Exceptions\SiriusCryptoError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \SodiumException
     * @throws \JsonException
     */
    public function wait(int $timeout = null): bool
    {
        if ($this->read_ok) {
            return true;
        }
        try {
            $timezone = new DateTimeZone('Asia/Almaty');
            $now = DateTime::createFromFormat('Y-m-d h:i:s', date('Y-m-d h:i:s'), $timezone);
            if ($timeout === 0) {
                return false;
            }
            if ($this->expiration_time != null) {
                $expires_time = $this->expiration_time;
            } elseif ($timeout !== null) {
                $expires_time = DateTime::createFromFormat('Y-m-d h:i:s', date('Y-m-d h:i:s', time() + $timeout), $timezone);
            } else {
                $expires_time = DateTime::createFromFormat('Y-m-d h:i:s', date('Y-m-d h:i:s', time() + 365), $timezone);
            }
            while ($now < $expires_time) {
                $timedelta = $expires_time->diff(new DateTime());
                $timeout = max($timedelta->s, 0);
                $payload = $this->tunnel->receive($timeout);
                $payload = $payload->payload;
                if (
                    array_key_exists('@type', $payload) &&
                    $payload['@type'] === self::MSG_TYPE &&
                    array_key_exists('~thread', $payload) &&
                    $payload['~thread']['thid'] === $this->id
                ) {
                    if (array_key_exists('exception', $payload) && $payload['exception']) {
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
            }
        } catch (SiriusTimeoutIO $e) {
            return false;
        }

        return false;
    }

    /**
     * Get response value.
     *
     * @return null
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * - SiriusPendingOperation: response was not received yet. Call walt(0) to safely check value persists.
     */
    public function getValue()
    {
        if (!$this->read_ok) {
            throw new SiriusPendingOperation();
        }
        return $this->value;
    }

    /**
     * Check if response was interrupted with exception
     *
     * @return bool True if request have done with exception
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * - SiriusPendingOperation: response was not received yet. Call walt(0) to safely check value persists.
     */
    public function hasException() : bool
    {
        if (!$this->read_ok) {
            throw new SiriusPendingOperation();
        }
        return $this->exception !== null;
    }

    /**
     * Get exception that have interrupted response routine on server-side.
     *
     * @return mixed|\Siruis\Errors\Exceptions\SiriusPromiseContextException|null
     * Exception instance or None if it does not exist
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     */
    public function getException()
    {
        if ($this->hasException()) {
            if (array_key_exists('indy', $this->exception) && $this->exception['indy']) {
                $indy_exc = $this->exception['indy'];
                $exc_class = ErrorCodeToException::parse($indy_exc['error_code']);
                return new $exc_class(
                    $indy_exc['error_code'],
                    [
                        'message' => $indy_exc['message'],
                        'indy_backtrace' => null
                    ]);
            }

            return new SiriusPromiseContextException(
                $this->exception['class_name'],
                $this->exception['printable']
            );
        }

        return null;
    }

    /**
     * Raise exception if exists
     *
     * @throws \Siruis\Errors\Exceptions\SiriusPendingOperation
     * @throws \Siruis\Errors\Exceptions\SiriusPromiseContextException
     * @throws \Siruis\Errors\Exceptions\SiriusValueEmpty
     * - SiriusValueEmpty: raises if exception is empty
     */
    public function throwException(): void
    {
        if ($this->hasException()) {
            throw $this->getException();
        }

        throw new SiriusValueEmpty();
    }
}