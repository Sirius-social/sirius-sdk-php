<?php


namespace Siruis\Agent\Microledgers;


class MerkleInfo
{
    /**
     * @var string
     */
    public $root_hash;

    /**
     * @var array
     */
    public $audit_path;

    public function __construct(string $root_hash, array $audit_path)
    {
        $this->root_hash = $root_hash;
        $this->audit_path = $audit_path;
    }
}