<?php

use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Messaging\Message;
use Siruis\Messaging\Type\Type;

$msgRegistry = [];

/**
 * Generate a message id.
 *
 * @return string
 */
function generate_id(): string
{
    return uniqid();
}

/**
 * @param $class
 * @param string $protocol
 * @param string|null $name
 *
 * @return void
 *
 * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
 */
function register_message_class($class, string $protocol, string $name = null): void
{
    global $msgRegistry;
    if (is_subclass_of($class, Message::class)) {
        $descriptor = $msgRegistry[$protocol] ?: [];
        if ($name) {
            $descriptor[$name] = $class;
        } else {
            $descriptor['*'] = $class;
        }
        $msgRegistry[$protocol] = $descriptor;
    } else {
        throw new SiriusInvalidMessageClass();
    }
}

/**
 * @param array $payload
 *
 * @return array
 *
 * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
 */
function restore_message_instance(array $payload): array
{
    global $msgRegistry;
    if (array_key_exists('@type', $payload)) {
        $typ = Type::fromString($payload['@type']);
        $descriptor = $msgRegistry[$typ->protocol] ?: null;

        if ($descriptor) {
            if (array_key_exists($typ->name, $descriptor)) {
                $cls = $descriptor[$typ->name];
            } elseif (array_key_exists('*', $descriptor)) {
                $cls = $descriptor['*'];
            } else {
                $cls = null;
            }
        } else {
            $cls = null;
        }

        if ($cls) {
            return [true, new $cls($payload)];
        }

        return [false, null];
    }

    return [false, null];
}
