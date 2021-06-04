<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;
use Siruis\Errors\Exceptions\SiriusFieldValueError;

class LimitedLengthStringField extends FieldBase
{
    public $base_types = [String_::class];
    private $max_len;

    /**
     * LimitedLengthStringField constructor.
     * @param int $max_len
     * @param bool $optional
     * @param bool $nullable
     * @throws SiriusFieldValueError
     */
    public function __construct(int $max_len, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        if ($max_len < 0) {
            throw new SiriusFieldValueError('max_length', $max_len, '> 0');
        }
        $this->max_len = $max_len;
    }

    public function _specific_validation($value)
    {
        if (!$value) {
            return 'empty string';
        }
        if (strlen($value) > $this->max_len) {
            return $value . ' longer than ' . $this->max_len . ' symbols';
        }
    }
}