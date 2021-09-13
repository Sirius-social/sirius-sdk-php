<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use ArrayObject;

class ProposedAttrib extends ArrayObject
{
    /**
     * @var array
     */
    public $data;

    public function __construct(string $name,
                                string $value = null,
                                string $mime_type = null,
                                string $referent = null,
                                string $cred_def_id = null,
                                ...$args)
    {
        parent::__construct(...$args);
        $this->data = [];
        $this->data['name'] = $name;
        if (!is_null($mime_type)) {
            $this->data['mime_type'] = $mime_type;
        }
        if (!is_null($value)) {
            $this->data['value'] = $value;
        }
        if (!is_null($referent)) {
            $this->data['referent'] = $referent;
        }
        if (!is_null($cred_def_id)) {
            $this->data['cred_def_id'] = $cred_def_id;
        }
    }

    public function toJson()
    {
        return $this->data;
    }
}