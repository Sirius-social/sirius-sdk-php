<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use ArrayObject;

class AttribTranslation extends ArrayObject
{
    /**
     * @var array
     */
    public $data;

    public function __construct(string $attrib_name, string $translation, ...$args)
    {
        parent::__construct(...$args);
        $this->data['attrib_name'] = $attrib_name;
        $this->data['translation'] = $translation;
    }

    public function toJson()
    {
        return $this->data;
    }
}