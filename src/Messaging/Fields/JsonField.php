<?php


namespace Siruis\Messaging\Fields;


use Exception;
use phpDocumentor\Reflection\Types\String_;

class JsonField extends LimitedLengthStringField
{
    public $base_types = [String_::class];

    /**
     * @param $value
     * @return string
     */
    public function _specific_validation($value)
    {
        $lim_str_err = parent::_specific_validation($value);
        if ($lim_str_err)
            return $lim_str_err;
        try {
            json_decode($value);
        } catch (Exception $exception) {
            return 'should be a valid JSON string \n';
        }
    }

}