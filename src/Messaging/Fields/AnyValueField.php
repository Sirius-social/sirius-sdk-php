<?php


namespace Siruis\Messaging\Fields;


class AnyValueField extends FieldBase
{
    public $base_types = [];

    public function _specific_validation($value)
    {
        return;
    }
}