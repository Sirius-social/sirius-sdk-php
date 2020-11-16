<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Array_;

class MapField extends FieldBase
{
    public $base_types = [Array_::class];
    protected $key_field;
    protected $value_field;

    public function __construct(FieldValidator $key_field, FieldValidator $value_field, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->key_field = $key_field;
        $this->value_field = $value_field;
    }

    public function _specific_validation($value)
    {
        foreach ($value as $k => $v) {
            $key_error = $this->key_field->validate($k);
            if ($key_error)
                return $key_error;
            $val_err = $this->value_field->validate($v);
            if ($val_err)
                return $val_err;
        }
    }
}