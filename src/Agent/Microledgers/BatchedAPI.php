<?php


namespace Siruis\Agent\Microledgers;


use http\Exception\RuntimeException;
use Siruis\Agent\Connections\AgentRPC;

class BatchedAPI extends AbstractBatchedAPI
{
    /**
     * @var AgentRPC
     */
    protected $api;
    /**
     * @var array
     */
    protected $names;
    /**
     * @var array|null
     */
    protected $external;

    public function __construct(AgentRPC $api, LedgerCache $external = null)
    {
        $this->api = $api;
        $this->names = [];
        $this->external = $external;
    }

    /**
     * @param $ledgers
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function open($ledgers): array
    {
        $names_to_open = [];
        foreach ($ledgers as $ledger) {
            if ($ledger instanceof AbstractMicroledger) {
                $names_to_open[] = $ledger->getName();
            } elseif (is_string($ledger)) {
                $names_to_open[] = $ledger;
            } else {
                throw new RuntimeException('Unexpected ledgers item type: '. gettype($ledger));
            }
        }
        sort($names_to_open);
        $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/open',
            [
                'names' => $names_to_open
            ]
        );
        $this->names = $names_to_open;
        return $this->states();
    }

    /**
     * @return void
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function close(): void
    {
        $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/close'
        );
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function states(): array
    {
        $states = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/states'
        );
        return $this->return_ledgers($states);
    }

    /**
     * @param $transactions
     * @param null $txn_time
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function append($transactions, $txn_time = null): array
    {
        $states = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/append_txns',
            [
                'txns' => $transactions,
                'txn_time' => $txn_time
            ]
        );
        return $this->return_ledgers($states);
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function commit(): array
    {
        $states = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/commit_txns'
        );
        return $this->return_ledgers($states);
    }

    /**
     * @return array
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function reset_uncommitted(): array
    {
        $states = $this->api->remoteCall(
            'did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/microledgers-batched/1.0/reset_uncommitted'
        );
        return $this->return_ledgers($states);
    }

    public function return_ledgers(array $states): array
    {
        $resp = [];
        foreach ($this->names as $name) {
            $state = $states[$name];
            $ledger = new Microledger($name, $this->api, $state);
            if (!is_null($this->external)) {
                if ($this->external->is_exists($name)) {
                    $ledger->assign_to($this->external->get($name));
                } else {
                    $this->external->set($name, $ledger);
                }
            }
            $resp[] = $ledger;
        }
        return $resp;
    }
}