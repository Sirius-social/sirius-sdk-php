<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class SerializedValueField extends FieldBase
{
    public $base_types = [String_::class];

    public function _specific_validation($value)
    {
        if (!$value && !$this->nullable) {
            return 'empty serialized value';
        }
    }
}