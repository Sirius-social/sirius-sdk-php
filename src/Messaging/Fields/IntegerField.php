<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Integer;

class IntegerField extends FieldBase
{
    public $base_types = [Integer::class];
    public function _specific_validation($value)
    {
        return;
    }
}