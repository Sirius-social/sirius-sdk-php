<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Boolean;

class BooleanField extends FieldBase
{
    public $base_types = [Boolean::class];
    public function _specific_validation($value)
    {
        return;
    }
}