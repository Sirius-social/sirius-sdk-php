<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\Integer;

class TimestampField extends FieldBase
{
    public $base_types = [Integer::class];
    protected $oldest_time = 1499906902;

    public function _specific_validation($value)
    {
        if ($value < $this->oldest_time)
            return 'should be greater than '.$this->oldest_time.' but was '.$value;
    }
}