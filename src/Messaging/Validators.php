<?php


namespace Siruis\Messaging;


use Siruis\Errors\Exceptions\SiriusValidationError;

class Validators
{
    CONST ID = "@id";
    CONST TYPE = "@type";
    CONST THREAD_DECORATOR = "~thread";
    CONST THREAD_ID = "thid";
    CONST PARENT_THREAD_ID = "pthid";
    CONST SENDER_ORDER = "sender_order";
    CONST RECEIVED_ORDERS = "received_orders";
    CONST THREADING_ERROR = "threading_error";
    CONST TIMING_ERROR = "timing_error";
    
    CONST TIMING_DECORATOR = "~timing";
    CONST IN_TIME = "in_time";
    CONST OUT_TIME = "out_time";
    CONST STALE_TIME = "stale_time";
    CONST EXPIRES_TIME = "expires_time";
    CONST DELAY_MILLI = "delay_milli";
    CONST WAIT_UNTIL_TIME = "wait_until_time";

    /**
     * @param array $partial
     * @param array $expected_attributes
     * @throws SiriusValidationError
     */
    public function check_for_attributes(array $partial, array $expected_attributes)
    {
        foreach ($expected_attributes as $attribute) {
            if (is_array($attribute)) {
                if (!in_array($attribute[0], $partial)) {
                   throw new SiriusValidationError("Attribute $attribute[0] is missing from message: $partial");
                }
                if ($partial[$attribute[0]] != $attribute[1]) {
                    throw new SiriusValidationError('Message.' . $attribute[0] . ': ' . $partial[$attribute[0]] .' != '. $attribute[1]);
                }
            }
            if (!in_array($attribute, $partial)) {
                throw new SiriusValidationError("Attribute $attribute is missing from message: $partial");
            }
        }
    }

    public static function validate_common_blocks(array $partial)
    {

    }

    /**
     * @param array $partial
     * @throws SiriusValidationError
     */
    private function _validate_thread_block(array $partial)
    {
        if (in_array(self::THREAD_DECORATOR, $partial)) {
            $thread = $partial[self::THREAD_DECORATOR];
            $this->check_for_attributes($thread, [self::THREAD_ID]);

            $thread_id = $thread[self::THREAD_ID];
            if ($partial[self::ID] && $thread_id == $partial[self::ID]) {
                throw new SiriusValidationError("Thread id $thread_id cannot be equal to outer id ". $partial[self::ID]);
            }
            if ($thread[self::PARENT_THREAD_ID]
                && $thread[self::PARENT_THREAD_ID] == $thread_id
                && $thread[self::PARENT_THREAD_ID] == $partial[self::ID]) {
                throw new SiriusValidationError('Parent thread id '. $thread[self::PARENT_THREAD_ID] .' must be different than thread id and outer id');
            }
            if ($thread[self::SENDER_ORDER] && $thread[self::SENDER_ORDER] < 0) {
                if (in_array(self::RECEIVED_ORDERS, $thread)) {

                }
            }

        }
    }
}