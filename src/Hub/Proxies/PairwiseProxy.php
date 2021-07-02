<?php


namespace Siruis\Hub\Proxies;


use Siruis\Agent\Pairwise\AbstractPairwiseList;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\SiriusInitializationError;
use Siruis\Hub\Core\Hub;

class PairwiseProxy extends AbstractPairwiseList
{
    /**
     * @var AbstractPairwiseList
     */
    protected $service;

    /**
     * PairwiseProxy constructor.
     * @throws SiriusInitializationError
     */
    public function __construct()
    {
        $this->service = Hub::current_hub()->get_pairwise_list();
    }

    public function create(Pairwise $pairwise)
    {
        return $this->service->create($pairwise);
    }

    public function update(Pairwise $pairwise)
    {
        return $this->service->update($pairwise);
    }

    public function is_exists(string $their_did)
    {
        return $this->service->is_exists($their_did);
    }

    public function ensure_exists(Pairwise $pairwise)
    {
        return $this->ensure_exists($pairwise);
    }

    public function load_for_did(string $their_did): ?Pairwise
    {
        return $this->service->load_for_did($their_did);
    }

    public function load_for_verkey(string $their_verkey): ?Pairwise
    {
        return $this->service->load_for_verkey($their_verkey);
    }

    public function start_loading()
    {
        return $this->service->start_loading();
    }

    /**
     * @inheritDoc
     */
    public function partial_load()
    {
        return $this->service->partial_load();
    }

    public function stop_loading()
    {
        return $this->service->stop_loading();
    }
}