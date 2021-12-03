<?php


namespace Siruis\Agent\Pairwise;


class Me
{
    /**
     * @var string|null
     */
    public $did;
    /**
     * @var string|null
     */
    public $verkey;
    /**
     * @var array|null
     */
    public $did_doc;

    /**
     * Me constructor.
     * @param string|null $did
     * @param string|null $verkey
     * @param array|null $did_doc
     */
    public function __construct(?string $did, ?string $verkey, array $did_doc = null)
    {
        $this->did = $did;
        $this->verkey = $verkey;
        $this->did_doc = $did_doc;
    }
}