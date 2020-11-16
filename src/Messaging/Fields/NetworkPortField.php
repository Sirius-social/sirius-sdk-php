<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Integer;

class NetworkPortField extends FieldBase
{
    public $base_types = [Integer::class];

    public function _specific_validation($value)
    {
        if ($value <= 0 || $value > 65535)
            return 'network port out of the range 0-65535';
    }
}