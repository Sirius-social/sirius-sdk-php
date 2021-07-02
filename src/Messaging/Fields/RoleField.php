<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\String_;

class RoleField extends FieldBase
{
    public $base_types = [String_::class, Null_::class];

    public function _specific_validation($value)
    {
        return;
    }
}