<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Microledgers\AbstractBatchedAPI;
use Siruis\Agent\Microledgers\AbstractMicroledger;
use Siruis\Agent\Microledgers\AbstractMicroledgerList;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class MicroledgersProxy extends AbstractMicroledgerList
{
    /**
     * @var AbstractMicroledgerList
     */
    protected $service;

    /**
     * MicroledgersProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $hub = Hub::current_hub();
        if (is_null($hub)) {
            throw new SiriusInitializationError('Hub not initialized');
        }

        $this->service = $hub->get_microledgers();
    }

    public function create(string $name, array $genesis)
    {
        return $this->service->create($name, $genesis);
    }

    public function ledger(string $name): AbstractMicroledger
    {
        return $this->service->ledger($name);
    }

    public function reset(string $name)
    {
        return $this->service->reset($name);
    }

    public function is_exists(string $name)
    {
        return $this->service->is_exists($name);
    }

    public function leaf_hash($txn)
    {
        return $this->service->leaf_hash($txn);
    }

    public function list()
    {
        return $this->service->list();
    }

    /**
     * @return \Siruis\Agent\Microledgers\AbstractBatchedAPI|null
     * @throws \Siruis\Errors\Exceptions\SiriusInitializationError
     */
    public function batched(): ?AbstractBatchedAPI
    {
        $hub = Hub::current_hub();
        if (is_null($hub)) {
            throw new SiriusInitializationError('Hub not initialized');
        }

        return $hub->get_microledgers()->batched();
    }
}