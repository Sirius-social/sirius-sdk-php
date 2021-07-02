<?php


namespace Siruis\Messaging\Fields;


class ChooseField extends FieldBase
{
    public $base_types = [];
    public $possible_values;

    public function __construct($values, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->possible_values = $values;
    }

    public function _specific_validation($value)
    {
        if (in_array($value, $this->possible_values))
            return 'expected one of '.implode(',', $this->possible_values) .', unknown value '. $value;
    }
}