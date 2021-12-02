<?php


namespace Siruis\Agent\Microledgers;

use RuntimeException;
use Siruis\Agent\Connections\AgentRPC;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\RPC\RawBytes;


class MicroledgerList extends AbstractMicroledgerList
{
    public const TTL = 60*60;
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

    /**
     * MicroledgerList constructor.
     * @param \Siruis\Agent\Connections\AgentRPC $api
     */
    public function __construct(AgentRPC $api)
    {
        $this->api = $api;
        $this->instances = new LedgerCache();
        $this->batched_api = new BatchedAPI($api, $this->instances);
    }

    /**
     * @return \Siruis\Agent\Microledgers\AbstractBatchedAPI
     */
    public function batched(): AbstractBatchedAPI
    {
        return $this->batched_api;
    }

    /**
     * @param string $name
     * @param array $genesis
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function create(string $name, array $genesis): array
    {
        $genesis_txns = [];
        foreach ($genesis as $txn) {
            if ($txn instanceof Transaction) {
                $genesis_txns[] = $txn->as_object();
            } elseif (is_array($txn)) {
                $txn = Transaction::create($txn);
                $genesis_txns[] = $txn->as_object();
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
     * @return \Siruis\Agent\Microledgers\AbstractMicroledger
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function ledger(string $name): AbstractMicroledger
    {
        if (!$this->instances->is_exists($name)) {
            $this->check_is_exists($name);
            $instance = new Microledger($name, $this->api);
            $this->instances->set($name, $instance);
        }
        return $this->instances->get($name);
    }

    /**
     * @param string $name
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function reset(string $name): void
    {
        $this->check_is_exists($name);
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

    /**
     * @param string $name
     * @return bool
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function is_exists(string $name): bool
    {
        return $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/is_exists',
            [
                'name' => $name
            ]
        );
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \JsonException
     */
    public function leaf_hash($txn)
    {
        if ($txn instanceof Transaction) {
            $data = mb_convert_encoding(json_encode($txn->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT), 'utf-8');
        } elseif (is_string($txn)) {
            $data = $txn;
        } else {
            throw new RuntimeException('Unexpected transaction type');
        }
        return $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/leaf_hash',
            [
                'data' => new RawBytes($data)
            ]
        );
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function list(): array
    {
        $collection = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers/1.0/list',
            [
                'name' => '*'
            ]
        );
        $metas = [];
        foreach ($collection as $item) {
            $metas[] = new LedgerMeta($item['name'], $item['uid'], $item['created']);
        }
        return $metas;
    }

    /**
     * @param string $name
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusContextError
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    protected function check_is_exists(string $name): void
    {
        if (!$this->instances->is_exists($name)) {
            $is_exists = $this->is_exists($name);
            if (!$is_exists) {
                throw new SiriusContextError('MicroLedger with name ' . $name . ' does not exists');
            }
        }
    }
}
