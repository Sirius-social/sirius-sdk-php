<?php


namespace Siruis\Messaging\Fields;


use Exception;
use phpDocumentor\Reflection\Types\String_;
use Siruis\Errors\Exceptions\SiriusFieldValueError;

class NetworkIpAddressField extends FieldBase
{
    public $base_types = [String_::class];
    public $non_valid_addresses = ['0.0.0.0', '0:0:0:0:0:0:0:0', '::'];

    public function _specific_validation($value)
    {
        $invalid_addresses = false;
        try {
            long2ip($value);
        } catch (Exception $exception) {
            $invalid_addresses = true;
        }
        if ($invalid_addresses || in_array($value, $this->non_valid_addresses))
            return 'invalid network ip address '. $value;
    }
}