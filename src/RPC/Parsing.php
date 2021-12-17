<?php

namespace Siruis\RPC;

use stdClass;
use RuntimeException;
use Siruis\Messaging\Message;
use Siruis\RPC\Futures\Future;
use Siruis\Messaging\Type\Type;
use Siruis\Encryption\Encryption;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\PurgeOptions;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;
use Siruis\Agent\Wallet\Abstracts\Ledger\PoolAction;
use Siruis\Agent\Wallet\Abstracts\KeyDerivationMethod;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;

class Parsing
{
    public const MSG_TYPE_FUTURE = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/future';
    public const CLS_MAP = [
        'application/cache-options' => CacheOptions::class,
        'application/purge-options' => PurgeOptions::class,
        'application/retrieve-record-options' => RetrieveRecordOptions::class,
        'application/nym-role' => NYMRole::class,
        'application/pool-action' => PoolAction::class,
        'application/key-derivation-method' => KeyDerivationMethod::class,
    ];

    /**
     * Serialize variable.
     *
     * @param $var
     * @return array
     * @throws \JsonException
     * @throws \SodiumException
     */
    public static function serialize_variable($var): array
    {
        $revert = array_flip(self::CLS_MAP);
        if ($var instanceof CacheOptions) {
            return [$revert[CacheOptions::class], $var->serialize()];
        }

        if ($var instanceof PurgeOptions) {
            return [$revert[PurgeOptions::class], $var->serialize()];
        }

        if ($var instanceof RetrieveRecordOptions) {
            return [$revert[RetrieveRecordOptions::class], $var->serialize()];
        }

        if ($var instanceof NYMRole) {
            return [$revert[NYMRole::class], $var->jsonSerialize()];
        }

        if ($var instanceof PoolAction) {
            return [$revert[PoolAction::class], $var->jsonSerialize()];
        }

        if ($var instanceof KeyDerivationMethod) {
            return [$revert[KeyDerivationMethod::class], $var->jsonSerialize()];
        }

        if ($var instanceof RawBytes) {
            return ['application/base64', Encryption::bytes_to_b64($var->toBytes())];
        }

        if (is_string($var)) {
            if (self::is_binary($var)) {
                return ['application/base64', Encryption::bytes_to_b64($var)];
            }

            return [null, $var];
        }

        return [null, $var];
    }

    /**
     * Deserialize varaible.
     *
     * @param $payload
     * @param string|null $typ
     * @return array|mixed|string
     * @throws \SodiumException
     */
    public static function deserialize_variable($payload, string $typ = null)
    {
        if (!$typ) {
            return $payload;
        }

        if ($typ === 'application/base64') {
            return Encryption::b64_to_bytes($payload);
        }

        if (array_key_exists($typ, self::CLS_MAP)) {
            $cls = self::CLS_MAP[$typ];
            if (is_subclass_of($cls, NYMRole::class)) {
                $inst = NYMRole::deserialize($payload);
            } elseif (is_subclass_of($cls, PoolAction::class)) {
                $inst = PoolAction::deserialize($payload);
            } elseif (is_subclass_of($cls, KeyDerivationMethod::class)) {
                $inst = KeyDerivationMethod::deserialize($payload);
            } else {
                $inst = new $cls();
                $inst->deserialize($payload);
            }
            return $inst;
        }

        throw new RuntimeException('Unexpected typ: '.$typ);
    }

    /**
     * Incapsulate param from the given param.
     *
     * @param $param
     * @return array
     * @throws \JsonException
     * @throws \SodiumException
     */
    public static function incapsulate_param($param): array
    {
        $array = self::serialize_variable($param);
        return [
            'mime_type' => $array[0],
            'payload' => $array[1]
        ];
    }

    /**
     * Deincapsulate param from the given array.
     *
     * @param array $packet
     * @return array|mixed|string
     * @throws \SodiumException
     */
    public static function deincapsulate_param(array $packet)
    {
        return self::deserialize_variable($packet['payload'], $packet['mime_type']);
    }

    /**
     * Build request from the given params.
     *
     * @param string $msg_type
     * @param \Siruis\RPC\Futures\Future $future
     * @param $params
     * @return \Siruis\Messaging\Message
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \SodiumException
     */
    public static function build_request(string $msg_type, Future $future, $params): Message
    {
        $typ = Type::fromString($msg_type);
        if (!in_array($typ->protocol, ['sirius_rpc', 'admin', 'microledgers', 'microledgers-batched'])) {
            throw new SiriusInvalidType('Expect sirius_rpc protocol');
        }
        $p = [];
        foreach ($params as $k => $v) {
            $p[$k] = self::incapsulate_param($v);
        }
        if (empty($p)) {
            $p = new stdClass();
        }
        return new Message([
            '@type' => $msg_type,
            '@promise' => $future->getPromise(),
            'params' => $p
        ]);
    }

    /**
     * Build response from given message.
     *
     * @param \Siruis\Messaging\Message $packet
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \SodiumException
     */
    public static function build_response(Message $packet): array
    {
        if ($packet->getType() === self::MSG_TYPE_FUTURE) {
            if ($packet['~thread'] !== null) {
                $parsed = [
                    'exception' => null,
                    'value' => null
                ];
                $exception = $packet['exception'];
                if ($exception) {
                    $parsed['exception'] = $exception;
                } else {
                    $value = $packet['value'];
                    if ($packet['is_tuple']) {
                        $parsed['value'] = [$value];
                    } elseif ($packet['is_bytes']) {
                        $parsed['value'] = Encryption::b64_to_bytes($value);
                    } else {
                        $parsed['value'] = $value;
                    }
                }
                return $parsed;
            }

            throw new SiriusInvalidPayloadStructure('Except ~thread decorator');
        }

        throw new SiriusInvalidType('Except message type '. self::MSG_TYPE_FUTURE);
    }

    /**
     * Check string is binary.
     *
     * @param string $string
     * @return bool
     */
    public static function is_binary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }
}
