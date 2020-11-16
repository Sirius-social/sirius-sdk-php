<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class NonEmptyStringField extends FieldBase
{
    public $base_types = [String_::class];

    public function _specific_validation($value)
    {
        if (!$value) {
            return 'empty string';
        }
        return;
    }
}