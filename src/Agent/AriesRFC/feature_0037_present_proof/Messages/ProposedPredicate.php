<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use ArrayObject;

class ProposedPredicate extends ArrayObject
{
    /**
     * @var array
     */
    public $data;

    public function __construct(string $name,
                                string $predicate,
                                string $threshold,
                                string $cred_def_id = null,
                                ...$args)
    {
        parent::__construct(...$args);
        $this->data = [];
        $this->data['name'] = $name;
        $this->data['predicate'] = $predicate;
        $this->data['threshold'] = $threshold;
        if (!is_null($cred_def_id)) {
            $this->data['cred_def_id'] = $cred_def_id;
        }
    }

    public function toJson()
    {
        return $this->data;
    }
}