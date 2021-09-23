<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use ArrayObject;

class ProposedAttrib extends ArrayObject
{
    public $data = [];

    public function __construct(
        string $name,
        string $value,
        string $mime_type = null,
        $input = array(),
        $flags = 0,
        $iterator_class = "ArrayIterator"
    )
    {
        parent::__construct($input, $flags, $iterator_class);
        $this->data['name'] = $name;
        if ($mime_type) {
            $this->data['mime-type'] = $mime_type;
        }
        $this->data['value'] = $value;
    }

    public function to_json()
    {
        return $this->data;
    }
}