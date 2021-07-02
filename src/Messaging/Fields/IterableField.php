<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Integer;
use Siruis\Errors\Exceptions\SiriusFieldTypeError;
use Siruis\Errors\Exceptions\SiriusFieldValueError;

class IterableField extends FieldBase
{
    public $base_types = [Array_::class];
    private $inner_field_type;
    private $min_length;
    private $max_length;

    /**
     * IterableField constructor.
     * @param FieldValidator $inner_field_type
     * @param null $min_length
     * @param null $max_length
     * @param bool $optional
     * @param bool $nullable
     * @throws SiriusFieldTypeError|SiriusFieldValueError
     */
    public function __construct(FieldValidator $inner_field_type,
                                $min_length = null, $max_length = null,
                                $optional = false, $nullable = false)
    {
        if (get_class($inner_field_type) != FieldValidator::class) {
            throw new SiriusFieldTypeError('inner_field_type', $inner_field_type, FieldValidator::class);
        }

        foreach (['min_length', 'max_length'] as $k) {
            $m = get_defined_vars()[$k];
            if ($m) {
                if (!is_int($m)) {
                    throw new SiriusFieldTypeError($k, $m, Integer::class);
                }
                if ($m < 0) {
                    throw new SiriusFieldValueError($k, $m, '> 0');
                }
            }
        }
        $this->inner_field_type = $inner_field_type;
        $this->min_length = $min_length;
        $this->max_length = $max_length;
        parent::__construct($optional, $nullable);
    }

    public function _specific_validation($value)
    {
        if ($this->min_length) {
            if (count($value) < $this->min_length) {
                return 'length should be at least '.$this->min_length;
            }
        }
        if ($this->max_length) {
            if (count($value) > $this->max_length) {
                return 'length should be at most '.$this->max_length;
            }
        }
        foreach ($value as $v) {
            $check_err = $this->inner_field_type->validate($v);
            if ($check_err)
                return $check_err;
        }
    }
}