<?php


namespace Siruis\Agent\Pairwise;


class Their extends TheirEndpoint
{
    /**
     * @var string|null
     */
    public $did;
    /**
     * @var string|null
     */
    public $label;
    /**
     * @var array|null
     */
    public $did_doc;

    /**
     * Their constructor.
     * @param string|null $did
     * @param string|null $label
     * @param string|null $endpoint
     * @param string|null $verkey
     * @param array|null $routing_keys
     * @param array|null $did_doc
     */
    public function __construct(?string $did,
                                ?string $label,
                                ?string $endpoint,
                                ?string $verkey,
                                ?array $routing_keys = null,
                                ?array $did_doc = null)
    {
        $this->did = $did;
        $this->label = $label;
        $this->did_doc = $did_doc;
        parent::__construct($endpoint, $verkey, $routing_keys);
    }
}