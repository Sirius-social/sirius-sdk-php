<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Mixed_;

class AnyField extends FieldBase
{
    public $base_types = [Mixed_::class];

    public function _specific_validation($value)
    {
        return;
    }
}