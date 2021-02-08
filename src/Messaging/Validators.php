<?php


namespace Siruis\Messaging;


use Exception;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Messaging\Fields\DIDField;
use Siruis\Messaging\Fields\ISODatetimeStringField;
use Siruis\Messaging\Fields\MapField;
use Siruis\Messaging\Fields\NonNegativeNumberField;

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
    public static function check_for_attributes(array $partial, array $expected_attributes)
    {
        foreach ($expected_attributes as $attribute) {
            if (is_array($attribute)) {
                if (!key_exists($attribute[0], $partial)) {
                   throw new SiriusValidationError("Attribute $attribute[0] is missing from message: $partial");
                }
                if ($partial[$attribute[0]] != $attribute[1]) {
                    throw new SiriusValidationError('Message.' . $attribute[0] . ': ' . $partial[$attribute[0]] .' != '. $attribute[1]);
                }
            }
            if (!key_exists($attribute, $partial)) {
                throw new SiriusValidationError("Attribute $attribute is missing from message: ".var_dump($partial));
            }
        }
    }

    /**
     * @param array $partial
     * @throws SiriusValidationError
     */
    public function validate_common_blocks(array $partial)
    {
        $this->_validate_thread_block($partial);
        $this->_validate_timing_block($partial);
    }

    /**
     * @param array $partial
     * @throws SiriusValidationError
     * @throws Exception
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
                $non_neg_num = new NonNegativeNumberField();
                $err = $non_neg_num->validate($thread[self::SENDER_ORDER]);
                if (!$err)
                    if (in_array(self::RECEIVED_ORDERS, $thread) && self::RECEIVED_ORDERS == $thread[self::RECEIVED_ORDERS]) {
                        $recv_ords = $thread[self::RECEIVED_ORDERS];
                        $map_field = new MapField(new DIDField(), $non_neg_num);
                        $err = $map_field->validate($recv_ords);
                    }
                if ($err)
                    throw new Exception($err);
            }
        }
    }

    /**
     * @param array $partial
     * @throws SiriusValidationError
     */
    private function _validate_timing_block(array $partial)
    {
        if (in_array(self::TIMING_DECORATOR, $partial)) {
            $timing = $partial[self::TIMING_DECORATOR];
            $non_neg_num = new NonNegativeNumberField();
            $iso_data = new ISODatetimeStringField();
            $expected_iso_fields = [self::IN_TIME, self::OUT_TIME, self::STALE_TIME, self::EXPIRES_TIME, self::WAIT_UNTIL_TIME];
            foreach ($expected_iso_fields as $f) {
                if (in_array($f, $timing)) {
                    $err = $iso_data->validate($timing[$f]);
                    if ($err)
                        throw new SiriusValidationError($err);
                }
            }
            if (in_array(self::DELAY_MILLI, $timing)) {
                $err = $non_neg_num->validate($timing[self::DELAY_MILLI]);
                if ($err)
                    throw new SiriusValidationError($err);
            }

            // In time cannot be greater than out time
            if (in_array(self::IN_TIME, $timing) && in_array(self::OUT_TIME, $timing)) {
                $t_in = $iso_data->parseFunc($timing[self::IN_TIME]);
                $t_out = $iso_data->parseFunc($timing[self::OUT_TIME]);
                if ($t_in > $t_out)
                    throw new SiriusValidationError(self::IN_TIME . ' cannot be greater than '. self::OUT_TIME);
            }

            // Stale time cannot be greater than expires time
            if (in_array(self::STALE_TIME, $timing) && in_array(self::EXPIRES_TIME, $timing)) {
                $t_stale = $iso_data->parseFunc($timing[self::STALE_TIME]);
                $t_exp = $iso_data->parseFunc($timing[self::OUT_TIME]);

                if ($t_stale > $t_exp)
                    throw new SiriusValidationError(self::STALE_TIME.' cannot be greater than '.self::EXPIRES_TIME);
            }
        }
    }
}