<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class DIDField extends FieldBase
{
    public $base_types = [String_::class];
    protected $valid_domains = ['sov', 'peer'];

    public function _specific_validation($value)
    {
        $did_parts = explode(':', $value);
        if (count($did_parts) == 3 && $did_parts[0] == 'did' && in_array($did_parts[1], $this->valid_domains)) {
            $validator = new Base58Field([16]);
            if (!$validator->validate($did_parts[2]))
                return null;
        }
        return 'Invalid DID '.$value;
    }
}