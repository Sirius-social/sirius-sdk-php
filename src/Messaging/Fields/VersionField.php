<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class VersionField extends LimitedLengthStringField
{
    public $base_types = [String_::class];
    protected $comp_num;

    public function __construct(int $max_len, array $comp_num = [3], $optional = false, $nullable = false)
    {
        parent::__construct($max_len, $optional, $nullable);
        $this->comp_num = $comp_num;
    }

    public function _specific_validation($value)
    {
        $lim_str_err = parent::_specific_validation($value);
        if ($lim_str_err)
            return $lim_str_err;
        $parts = explode('.', $value);
        if (!in_array(count($parts), $this->comp_num))
            return 'version consists of '.count($parts).' components, but it should contain '.$this->comp_num;
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                return 'version component should contain only digits';
            }
        }

        return null;
    }

}