<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class HexField extends FieldBase
{
    public $base_types = [String_::class];
    protected $length;

    public function __construct($length = null, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->length = $length;
    }

    public function _specific_validation($value)
    {
        try {
            intval($value, 16);
        } catch (\Exception $exception) {
            return 'invalid hex number '. $value;
        }
        if ($this->length && strlen($value) != $this->length)
            return 'length should be '.$this->length.' length';
    }
}