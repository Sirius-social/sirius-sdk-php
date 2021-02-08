<?php


namespace Siruis\Agent\Microledgers;


class AuditProof extends MerkleInfo
{
    /**
     * @var int
     */
    public $ledger_size;

    public function __construct(string $root_hash, array $audit_path, int $ledger_size)
    {
        parent::__construct($root_hash, $audit_path);
        $this->ledger_size = $ledger_size;
    }
}