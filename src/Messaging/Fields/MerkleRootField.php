<?php


namespace Siruis\Messaging\Fields;


use phpDocumentor\Reflection\Types\String_;

class MerkleRootField extends Base58Field
{
    public $base_types = [String_::class];

    public function __construct($optional = false, $nullable = false)
    {
        parent::__construct([32], $optional, $nullable);
    }
}