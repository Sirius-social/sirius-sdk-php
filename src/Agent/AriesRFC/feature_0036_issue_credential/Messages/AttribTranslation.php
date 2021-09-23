<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use ArrayObject;

class AttribTranslation extends ArrayObject
{
    public $data;

    public function __construct(
        string $attrib_name,
        string $translation,
        $input = array(),
        $flags = 0,
        $iterator_class = "ArrayIterator"
    )
    {
        parent::__construct($input, $flags, $iterator_class);
        $this->data['attrib_name'] = $attrib_name;
        $this->data['translation'] = $translation;
    }

    public function to_json()
    {
        return $this->data;
    }
}