<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Array_;

class AnyMapField extends FieldBase
{
    public $base_types = [Array_::class];

    public function _specific_validation($value)
    {
        return;
    }
}