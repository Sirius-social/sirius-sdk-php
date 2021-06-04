<?php


namespace Siruis\Messaging\Fields;


use Exception;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\String_;

class StringifiedNonNegativeNumberField extends NonNegativeNumberField
{
    public $base_types = [String_::class, Integer::class];

    public function _specific_validation($value)
    {
        try {
            parent::_specific_validation(intval($value));
        } catch (Exception $e) {
            return 'stringified int expected, but was '.$value;
        }
    }
}