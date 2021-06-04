<?php


namespace Siruis\Messaging\Fields;


use ArithmeticError;
use TypeError;

class FixedLengthField extends FieldBase
{
    private $length;
    /**
     * FixedLengthField constructor.
     * @param int $length
     * @param bool $optional
     * @param bool $nullable
     */
    public function __construct(int $length, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        if (!is_int($length)) {
            throw new TypeError('length should be integer');
        }
        if ($length < 1) {
            throw new ArithmeticError('should be greater than 0');
        }
        $this->length = $length;
    }

    public function _specific_validation($value)
    {
        if (strlen($value) != $this->length) {
            return "$value should have length $this->length";
        }
    }
}