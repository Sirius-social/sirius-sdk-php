<?php

namespace Siruis\RPC;

use RuntimeException;
use Siruis\Agent\Wallet\Abstracts\CacheOptions;
use Siruis\Agent\Wallet\Abstracts\KeyDerivationMethod;
use Siruis\Agent\Wallet\Abstracts\Ledger\NYMRole;
use Siruis\Agent\Wallet\Abstracts\Ledger\PoolAction;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Agent\Wallet\Abstracts\PurgeOptions;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidPayloadStructure;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;
use Siruis\RPC\Futures\Future;
use stdClass;

class Parsing {
    const MSG_TYPE_FUTURE = 'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/sirius_rpc/1.0/future';
    const CLS_MAP = [
        'application/cache-options' => CacheOptions::class,
        'application/purge-options' => PurgeOptions::class,
        'application/retrieve-record-options' => RetrieveRecordOptions::class,
        'application/nym-role' => NYMRole::class,
        'application/pool-action' => PoolAction::class,
        'application/key-derivation-method' => KeyDerivationMethod::class,
    ];

    const CLS_MAP_REVERT = [];

    /**
     * @param mixed $var input variable
     * @return array
     * @throws SodiumException
     */
    public static function serialize_variable($var)
    {
        $revert = array_flip(self::CLS_MAP);
        if ($var instanceof CacheOptions) {
            return [$revert[CacheOptions::class], $var->serialize()];
        } elseif ($var instanceof PurgeOptions) {
            return [$revert[PurgeOptions::class], $var->serialize()];
        } elseif ($var instanceof RetrieveRecordOptions) {
            return [$revert[RetrieveRecordOptions::class], $var->serialize()];
        } elseif ($var instanceof NYMRole) {
            return [$revert[NYMRole::class], $var->serialize()];
        } elseif ($var instanceof PoolAction) {
            return [$revert[PoolAction::class], $var->serialize()];
        } elseif ($var instanceof KeyDerivationMethod) {
            return [$revert[KeyDerivationMethod::class], $var->serialize()];
        } elseif (is_string($var)) {
            if (self::contains_any_multibyte($var)) {
                return ['application/base64', Encryption::bytes_to_b64($var)];
            } else {
                return [null, $var];
            }
        } else {
            return [null, $var];
        }
    }


    public static function deserialize_variable($payload, string $typ = null)
    {
        if (!$typ) {
            return $payload;
        } elseif ($typ == 'application/base64') {
            return Encryption::b64_to_bytes($payload);
        } elseif (key_exists($typ, self::CLS_MAP)) {
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
        } else {
            throw new RuntimeException('Unexpected typ: '.$typ);
        }
    }

    /**
     * @param $param
     * @return array
     * @throws SodiumException
     */
    public static function incapsulate_param($param)
    {
        $array = self::serialize_variable($param);
        return [
            'mime_type' => $array[0],
            'payload' => $array[1]
        ];
    }

    public static function deincapsulate_param(array $packet)
    {
        return self::deserialize_variable($packet['payload'], $packet['mime_type']);
    }

    /**
     * @param string $msg_type
     * @param Future $future
     * @param array|stdClass $params
     * @return Message
     * @throws SiriusInvalidType
     * @throws SodiumException
     * @throws SiriusInvalidMessageClass
     */
    public static function build_request(string $msg_type, Future $future, $params)
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
     * @param Message $packet
     * @return null[]
     * @throws SiriusInvalidPayloadStructure
     * @throws SiriusInvalidType
     * @throws SodiumException
     */
    public static function build_response(Message $packet)
    {
        if ($packet->getType() == self::MSG_TYPE_FUTURE) {
            $thread = key_exists('~thread', (array)$packet) ? $packet['~~thread'] : null;
            if ($packet['~thread']) {
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
                    } elseif($packet['is_bytes']) {
                        $parsed['value'] = Encryption::b64_to_bytes($value);
                    } else {
                        $parsed['value'] = $value;
                    }
                }
                return $parsed;
            } else {
                throw new SiriusInvalidPayloadStructure('Except ~thread decorator');
            }
        } else {
            throw new SiriusInvalidType('Except message type '. MSG_TYPE_FUTURE);
        }
    }

    protected static function contains_any_multibyte(string $string)
    {
        return !mb_check_encoding($string, 'ASCII') && mb_check_encoding($string, 'UTF-8');
    }
}
