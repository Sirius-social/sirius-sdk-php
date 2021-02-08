<?php


namespace Siruis\Agent\Pairwise;


class Me
{
    public $did;
    public $verkey;
    /**
     * @var array|null
     */
    public $did_doc;

    public function __construct($did, $verkey, array $did_doc = null)
    {
        $this->did = $did;
        $this->verkey = $verkey;
        $this->did_doc = $did_doc;
    }
}