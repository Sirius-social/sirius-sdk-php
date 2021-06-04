<?php


namespace Siruis\Messaging\Fields;


class MessageField extends FieldBase
{
    public $base_types = [];
    protected $message_type;

    public function __construct($message_type, $optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->message_type = $message_type;
    }

    public function _specific_validation($value)
    {
        if ($value instanceof $this->message_type)
            return;
        else
            return 'value cannot be represented as '.$value.' due to: '.$this->message_type;
    }
}