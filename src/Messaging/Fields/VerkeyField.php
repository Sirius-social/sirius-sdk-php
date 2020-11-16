<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class VerkeyField extends FieldBase
{
    public $base_types = [String_::class];
    protected $b58abbr;
    protected $b58full;

    public function __construct($optional = false, $nullable = false)
    {
        parent::__construct($optional, $nullable);
        $this->b58abbr = new AbbreviatedVerkeyField();
        $this->b58full = new FullVerkeyField();
    }


    public function _specific_validation($value)
    {
        $err_ab = $this->b58abbr->validate($value);
        $err_fl = $this->b58full->validate($value);
        if ($err_ab || $err_fl)
            return 'Neither a full verkey nor an abbreviated one. One of these errors should be resolved: \n '
                .$err_ab. ' \n' . $err_fl;
    }
}