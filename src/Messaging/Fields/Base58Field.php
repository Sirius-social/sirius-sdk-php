<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;
use StephenHill\Base58;

class Base58Field extends FieldBase
{
    public $base_types = [String_::class];
    public $byte_lengths;

    public function __construct(array $byte_lengths = null, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->byte_lengths = $byte_lengths;

    }

    public function _specific_validation($value)
    {
        if ($this->byte_lengths) {
            $b58 = new Base58();
            $b58len = strlen($b58->decode($value));
            if (!in_array($b58len, $this->byte_lengths)) {
                return 'b58 decoded value length '.$b58len.' should be one of '.implode(',', $this->byte_lengths);
            }
        }
    }
}