<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Integer;

class NonNegativeNumberField extends FieldBase
{
    public $base_types = [Integer::class];

    public function _specific_validation($value)
    {
        if ($value < 0)
            return 'negative value';
    }
}