<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;
use function PHPUnit\Framework\stringStartsWith;

class AbbreviatedVerkeyField extends FieldBase
{
    public $base_types = [String_::class];
    protected $validator;

    public function __construct($optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->validator = new Base58Field([16]);
    }

    public function _specific_validation($value)
    {
        if (substr($value, 0, 1) != '~')
            return 'should start with a ~';
        return $this->validator->validate(substr($value, 1));
    }
}