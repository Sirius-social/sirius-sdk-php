<?php


namespace Siruis\Messaging\Fields;


abstract class FieldBase implements FieldValidator
{
    public $base_types = [];
    public $optional = false;
    public $nullable = false;

    public function __construct($optional = false, $nullable = false)
    {
        $this->optional = $optional;
        $this->nullable = $nullable;
    }

    /**
     * @inheritDoc
     */
    public function validate($value): string
    {
        if ($this->nullable && !$value) {
            return;
        }
        $type_err = $this->__type_check($value);
        $spec_err = $this->_specific_validation($value);
        if ($type_err) {
            return $type_err;
        }
        if ($spec_err) {
            return $spec_err;
        }
        return;
    }


    abstract public function _specific_validation($value);

    private function __type_check($value): string
    {
        if (!$this->base_types) {
            return;
        }
        foreach ($this->base_types as $type) {
            if ($value instanceof $type) {
                return;
            }
        }
        return $this->_wrong_type_msg($value);
    }

    private function _wrong_type_msg($value): string
    {
        return 'expected types "'. implode(',', $this->base_types) . '", got "' . gettype($value) . '"';
    }
}