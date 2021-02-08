<?php


namespace Siruis\Agent\Pairwise;


class Pairwise
{
    /**
     * @var Me
     */
    public $me;
    /**
     * @var Their
     */
    public $their;
    /**
     * @var array|null
     */
    public $metadata;

    public function __construct(Me $me, Their $their, array $metadata = null)
    {

        $this->me = $me;
        $this->their = $their;
        $this->metadata = $metadata;
    }
}