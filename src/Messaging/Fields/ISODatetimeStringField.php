<?php


namespace Siruis\Messaging\Fields;


use DateTime;
use phpDocumentor\Reflection\Types\String_;

class ISODatetimeStringField extends FieldBase
{
    public $base_types = [String_::class];
    public $date;

    public function _specific_validation($value)
    {
        try {
            DateTime::createFromFormat('Y-m-d\TH:i:s+', $value);
        } catch (\Exception $exception) {
            return $value.' is an invalid ISO';
        }
    }

    public function parseFunc($date)
    {
        return DateTime::createFromFormat('Y-m-d\TH:i:s+', $date);
    }
}