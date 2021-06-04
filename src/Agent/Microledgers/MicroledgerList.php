<?php


namespace Siruis\Agent\Microledgers;

use RuntimeException;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\RPC\RawBytes;


class MicroledgerList extends AbstractMicroledgerList
{
    const TTL = 60*60;
    /**
     * @var AgentRPC
     */
    public $api;
    /**
     * @var array
     */
    public $instances;
    /**
     * @var BatchedAPI
     */
    public $batched_api;

    public function __construct(AgentRPC $api)
    {
        $this->api = $api;
        $this->instances = new LedgerCache();
        $this->batched_api = new BatchedAPI($api, $this->instances);
    }

    public function batched(): AbstractBatchedAPI
    {
        return $this->batched_api;
    }

    /**
     * @param string $name
     * @param array $genesis
     * @return array
     * @throws SiriusContextError
     */
    public function create(string $name, array $genesis): array
    {
        $genesis_txns = [];
        foreach ($genesis as $txn) {
            if ($txn instanceof Transaction) {
                array_push($genesis_txns, $txn->as_object());
            } elseif (is_array($txn)) {
                $txn = Transaction::create($txn);
                array_push($genesis_txns, $txn->as_object());
            } else {
                throw new RuntimeException('Unexpected transaction type');
            }
        }
        $instance = new Microledger($name, $this->api);
        $txns = $instance->init($genesis_txns);
        $this->instances->set($name, $instance);
        return [$instance, $txns];
    }

    /**
     * @param string $name
     * @return AbstractMicroledger
     * @throws SiriusContextError
     */
    public function ledger(string $name): AbstractMicroledger
    {
        if (!$this->instances->is_exists($name)) {
            $this->__check_is_exists($name);
            $instance = new Microledger($name, $this->api);
            $this->instances->set($name, $instance);
        }
        return $this->instances->get($name);
    }

    /**
     * @param string $name
     * @throws SiriusContextError
     */
    public function reset(string $name)
    {
        $this->__check_is_exists($name);
        $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/reset',
            [
                'name' => $name
            ]
        );
        if ($this->instances->is_exists($name)) {
            $this->instances->delete($name);
        }
    }

    public function is_exists(string $name): bool
    {
        return $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/is_exists',
            [
                'name' => $name
            ]
        );
    }

    public function leaf_hash($txn)
    {
        if ($txn instanceof Transaction) {
            $data = mb_convert_encoding(json_encode($txn->payload, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT), 'utf-8');
        } elseif (is_string($txn)) {
            $data = $txn;
        } else {
            throw new RuntimeException('Unexpected transaction type');
        }
        $resp = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/leaf_hash',
            [
                'data' => new RawBytes($data)
            ]
        );
        return $resp;
    }

    public function list()
    {
        $collection = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/list',
            [
                'name' => '*'
            ]
        );
        $metas = [];
        foreach ($collection as $item) {
            array_push($metas, new LedgerMeta($item['name'], $item['uid'], $item['created']));
        }
        return $metas;
    }

    /**
     * @param string $name
     * @throws SiriusContextError
     */
    protected function __check_is_exists(string $name)
    {
        if (!$this->instances->is_exists($name)) {
            $is_exists = $this->is_exists($name);
            if (!$is_exists) {
                throw new SiriusContextError('MicroLedger with name ' . $name . ' does not exists');
            }
        }
    }
}
