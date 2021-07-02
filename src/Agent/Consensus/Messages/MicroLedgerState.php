<?php


namespace Siruis\Agent\Consensus\Messages;


use ArrayObject;
use Siruis\Agent\Microledgers\AbstractMicroledger;

class MicroLedgerState extends ArrayObject
{
    public $payload;
    public $name;
    public $seq_no;
    public $size;
    public $uncommitted_size;
    public $root_hash;
    public $uncommitted_root_hash;
    public $hash;

    public function __construct($array = array(), $flags = 0, $iteratorClass = "ArrayIterator")
    {
        parent::__construct($array, $flags, $iteratorClass);
        $this->payload = $array;
        $this->name = $this->getName();
        $this->seq_no = $this->getSeqNo();
        $this->uncommitted_size = $this->getUncommittedSize();
        $this->root_hash = $this->getRootHash();
        $this->uncommitted_root_hash = $this->getUncommittedRootHash();
        $this->hash = $this->getHash();
    }

    public static function from_ledger(AbstractMicroledger $ledger): MicroLedgerState
    {
        return new MicroLedgerState(
            [
                'name' => $ledger->getName(),
                'seq_no' => $ledger->getSeqNo(),
                'size' => $ledger->getSize(),
                'uncommitted_size' => $ledger->getUncommittedSize(),
                'root_hash' => $ledger->getRootHash(),
                'uncommitted_root_hash' => $ledger->getUncommittedRootHash()
            ]
        );
    }

    public function is_filled(): bool
    {
        $keys = ['name', 'seq_no', 'size', 'uncommitted_size', 'root_hash', 'uncommitted_root_hash'];
        return !array_diff_key(array_flip($keys), (array)$this);
    }

    public function getName()
    {
        return $this->payload['name'];
    }

    public function setName(int $value)
    {
        $this->payload['name'] = $value;
    }

    public function getSeqNo()
    {
        return $this->payload['seq_no'];
    }

    public function setSeqNo(int $value)
    {
        $this->payload['seq_no'] = $value;
    }

    public function getSize()
    {
        return $this->payload['size'];
    }

    public function setSize(int $value)
    {
        $this->payload['size'] = $value;
    }

    public function getUncommittedSize()
    {
        return $this->payload['uncommitted_size'];
    }

    public function setUncommittedSize(int $value)
    {
        $this->payload['uncommitted_size'] = $value;
    }

    public function getRootHash()
    {
        return $this->payload['root_hash'];
    }

    public function setRootHash(string $value)
    {
        $this->payload['root_hash'] = $value;
    }

    public function getUncommittedRootHash()
    {
        return $this->payload['uncommitted_root_hash'];
    }

    public function setUncommittedRootHash(string $value)
    {
        $this->payload['uncommitted_root_hash'] = $value;
    }

    public function getHash(): string
    {
        $dump = json_encode($this->payload);
        return md5($dump);
    }
}