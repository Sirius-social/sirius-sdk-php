<?php


namespace Siruis\Messaging\Fields;


class ConstantField extends FieldBase
{
    public $base_types = [];
    private $value;

    public function __construct($value, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->value = $value;
    }

    public function _specific_validation($value)
    {
        if ($value != $this->value)
            return 'has to be equal' . $this->value;
    }
}