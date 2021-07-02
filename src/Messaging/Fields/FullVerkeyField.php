<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class FullVerkeyField extends FieldBase
{
    public $base_types = [String_::class];
    protected $validator;

    public function __construct($optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->validator = new Base58Field([32]);
    }

    public function _specific_validation($value)
    {
        return $this->validator->validate($value);
    }
}